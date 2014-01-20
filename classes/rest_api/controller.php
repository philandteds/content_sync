<?php
/**
 * @package ContentSync
 * @class   ContentSyncController
 * @author  Serhey Dolgushev <dolgushev.serhey@gmail.com>
 * @date    05 Jan 2014
 **/

class ContentSyncController extends ezpRestMvcController
{
	public function doStart() {
		$result = array(
			'_tag'       => 'response',
			'collection' => array(
				'status' => 'ERROR'
			)
		);

		if(
			isset( $this->request->post['request'] ) === false
			&& isset( $this->request->get['request'] ) === false
		) {
			$result['collection']['description'] = '"request" is not specified';
			return $this->result( $result, 500 );
		}

		$DOMDocument = new DOMDocument();
		$xml = null;
		if( isset( $this->request->post['request'] ) ) {
			$xml = $this->request->post['request'];
		} elseif( $this->request->get['request'] ) {
			$xml = $this->request->get['request'];
		}
		if( @$DOMDocument->loadXML( $xml ) === false ) {
			$result['collection']['description'] = '"request" is not valid XML';
			return $this->result( $result, 500 );
		}

		if(
			isset( $this->request->get['cli'] )
			|| isset( $this->request->post['cli'] )
		) {
			$file = 'var/cache/' . uniqid( 'content_sync', true ) . '.xml';
			if( $DOMDocument->save( $file ) === false ) {
				$result['collection']['description'] = 'Could not save object data to file';
			}
			chmod( $file, 0777 );

			$importCommand     = '$(which php) extension/content_sync/bin/php/import.php'
				. ' --object_data_file="' . $file . '"'
				. ' --creator_id=' . eZUser::currentUserID()
				. ' >> var/log/content_sync_import.log 2>&1';
			$removeFileCommand = 'rm -f ' . $file;
			exec( '(' . $importCommand . ' && ' . $removeFileCommand . ') &' );

			$result['collection']['status']      = 'SUCCESS';
			$result['collection']['description'] = 'Import started in background';
			return $this->result( $result );
		}

		try{
			self::handleObjectData( $xml );
		} catch( Exception $e ) {
			$result['collection']['description'] = $e->getMessage();
			return $this->result( $result, 500 );
		}

		$result['collection']['status']      = 'SUCCESS';
		$result['collection']['description'] = 'Import is done';
		return $this->result( $result );
	}

	public static function handleObjectData( $objectData ) {
		$startTime = microtime( true );
		$error     = null;
		$result    = array();

		$import = ContentSyncImport::getInstance();
		try{
			$result = $import->process( $objectData );
		} catch( Exception $e ) {
			$error = $e->getMessage();
		}

		$status = isset( $result['status'] ) ? $result['status'] : ContentSyncLogImport::STATUS_SKIPPED;

		$log = new ContentSyncLogImport();
		$log->setAttribute( 'object_data', $objectData );
		$log->setAttribute( 'status', $status );
		if( isset( $result['object_id'] ) ) {
			$log->setAttribute( 'object_id', $result['object_id'] );
		}
		if( isset( $result['object_version'] ) ) {
			$log->setAttribute( 'object_version', $result['object_version'] );
		}
		if( isset( $result['result'] ) ) {
			$log->setAttribute( 'result', $result['result'] );
		}
		if( $error !== null ) {
			$log->setAttribute( 'error', $error );
		}
		$log->setAttribute( 'import_time', microtime( true ) - $startTime );
		$log->store();

		return true;
	}

	protected function result( $feed, $status = 200 ) {
		$result = new ezpRestMvcResult();
		$result->variables['feed']   = $feed;
		$result->variables['status'] = $status;
		return $result;
	}
}



