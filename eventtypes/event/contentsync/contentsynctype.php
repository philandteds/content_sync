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

	public function __construct() {
		$this->eZWorkflowEventType(
			self::TYPE_ID,
			ezpI18n::tr( 'extension/contnet_sync', 'Content synchronization' )
		);
		$this->setTriggerTypes(
			array(
				'content' => array(
					'publish' => array( 'after' )
				)
			)
		);
	}

	public function execute( $process, $event ) {
		$parameters = $process->attribute( 'parameter_list' );
		$object     = eZContentObject::fetch( $parameters['object_id'] );

		if( $object instanceof eZContentObject ) {
			self::requestContentSync( $object, $object->attribute( 'current' ), $event );
		}

		return eZWorkflowType::STATUS_ACCEPTED;
	}

	public static function requestContentSync( $object, $version, $event ) {
		$objectAttributes = '<r></r>';

		$data = array(
			'request' => $objectAttributes
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
		// Dsiable "HTTP/1.1 100 Continue" from response
		curl_setopt( $curl, CURLOPT_HTTPHEADER, array( 'Expect:' ) );

		$response = curl_exec( $curl );
		$info     = curl_getinfo( $curl );
		$header   = trim( substr( $response, 0, $info['header_size'] ) );
		$body     = trim( substr( $response, $info['header_size'] ) );

		$log = new ContentSyncLogRequest();
		$log->setAttribute( 'object_id', $object->attribute( 'id' ) );
		$log->setAttribute( 'object_version', $version->attribute( 'version' ) );
		$log->setAttribute( 'object_data', $objectAttributes );
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
}

eZWorkflowEventType::registerEventType( ContentSyncType::TYPE_ID, 'ContentSyncType' );
