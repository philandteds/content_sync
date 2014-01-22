<?php
/**
 * @package ContentSync
 * @class   ContentSyncType
 * @author  Serhey Dolgushev <dolgushev.serhey@gmail.com>
 * @date    09 Jan 2014
 **/

class ContentSyncType extends eZWorkflowEventType
{
	const TYPE_ID = 'contentsync';

	protected static $isEnabled = true;

	public function __construct() {
		$this->eZWorkflowEventType(
			self::TYPE_ID,
			ezpI18n::tr( 'extension/contnet_sync', 'Content synchronization' )
		);
		$this->setTriggerTypes(
			array(
				'content' => array(
					'publish'        => array( 'after' ),
					'addlocation'    => array( 'after' ),
					'removelocation' => array( 'after' )
				)
			)
		);
	}

	public function execute( $process, $event ) {
		if( self::isEnabled() === false ) {
			return eZWorkflowType::STATUS_ACCEPTED;
		}

		$parameters = $process->attribute( 'parameter_list' );
		$object     = null;

		// content_removelocation operation has no object_id parameter
		if( $parameters['module_function'] == 'removelocation' ) {
			$object = eZContentObject::fetch( eZHTTPTool::instance()->postVariable( 'ContentObjectID' ) );
		} else {
			$object = eZContentObject::fetch( $parameters['object_id'] );
		}

		if( $object instanceof eZContentObject ) {
			$objectsToSync = self::getObjectsToSync( $object );
			foreach( $objectsToSync as $info ) {
				self::requestContentSync( $info['object'], $info['version'], $event );
			}
		}

		return eZWorkflowType::STATUS_ACCEPTED;
	}

	public static function getObjectsToSync( eZContentObject $object, $version = null ) {
		$syncHander = ContentSyncSerializeBase::get( $object );
		if( $syncHander instanceof ContentSyncSerializeBase ) {
			return $syncHander->getObjectsToSync( $object, $version );
		}
	}

	public static function requestContentSync( eZContentObject $object, $version, $event ) {
		$syncHander = ContentSyncSerializeBase::get( $object );
		if( $syncHander instanceof ContentSyncSerializeBase === false ) {
			return false;
		}

		$objectData = $syncHander->getObjectData( $object, $version );
		$data       = array(
			'request' => $objectData
		);
		if( (bool) $event->attribute( 'data_int1' ) ) {
			$data['cli'] = 1;
		}

		$curl = curl_init();
		curl_setopt( $curl, CURLOPT_URL, $event->attribute( 'url' ) );
		curl_setopt( $curl, CURLOPT_TIMEOUT, 30 );
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $curl, CURLOPT_POST, true );
        curl_setopt( $curl, CURLOPT_POSTFIELDS, $data );
		curl_setopt( $curl, CURLOPT_HEADER, true );
		// Avoid "HTTP/1.1 100 Continue" from response
		curl_setopt( $curl, CURLOPT_HTTPHEADER, array( 'Expect:' ) );

		$response = curl_exec( $curl );
		$info     = curl_getinfo( $curl );
		$header   = trim( substr( $response, 0, $info['header_size'] ) );
		$body     = trim( substr( $response, $info['header_size'] ) );

		$log = new ContentSyncLogRequest();
		$log->setAttribute( 'object_id', $object->attribute( 'id' ) );
		$log->setAttribute( 'object_version', $version->attribute( 'version' ) );
		$log->setAttribute( 'object_data', $objectData );
		$log->setAttribute( 'url', $info['url'] );
		$log->setAttribute( 'response_status', $info['http_code'] );
		$log->setAttribute( 'response_headers', $header );
		$log->setAttribute( 'response_time', $info['total_time'] );
		$log->setAttribute( 'response', $body );
		if( curl_error( $curl ) ){
			$log->setAttribute( 'response_error', curl_error( $curl ) );
		}
		$log->store();
	}

	public static function getObjectData( eZContentObject $object, eZContentObjectVersion $version ) {

	}

	public function typeFunctionalAttributes() {
		return array(
			'url'
		);
	}

	public function attributeDecoder( $event, $attr ) {
		switch( $attr ) {
			case 'url': {
				return $event->attribute( 'data_text1' ) . '/api/content_sync/v1/content_sync/start';
			}
		}
	}

	public function fetchHTTPInput( $http, $base, $event ) {
		if( $http->hasPostVariable( 'StoreButton' ) === false ) {
			return true;
		}

		if( $http->hasPostVariable( 'server_url' ) ) {
			$event->setAttribute( 'data_text1', trim( $http->postVariable( 'server_url' ) ) );
		}

		$event->setAttribute( 'data_int1', (int) $http->hasPostVariable( 'cli_mode' ) );
	}

	public static function isEnabled() {
		return self::$isEnabled == true;
	}

	public static function disable() {
		self::$isEnabled = false;
	}

	public static function enabled() {
		self::$isEnabled = true;
	}
}

eZWorkflowEventType::registerEventType( ContentSyncType::TYPE_ID, 'ContentSyncType' );
