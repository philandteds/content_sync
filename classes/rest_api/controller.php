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
/*
		if( isset( $this->request->get['cli'] ) ) {
			$sourceFile = eZINI::instance( 'ljimport.ini' )->variable( 'General', 'SourceFile' );
			if( $DOMDocument->save( $sourceFile ) === false ) {
				throw new Exception( 'Could not save XML to file' );
			}
			exec( '$(which php) extension/lj_import/bin/php/import.php > var/log/cli_import.log &' );
		} else {
			$moduleRepositories = eZModule::activeModuleRepositories( false );
			eZModule::setGlobalPathList( $moduleRepositories );

			$configs   = array(
				'ljImportConfigProductImage',
				'ljImportConfigProductSKU',
				'ljImportConfigPrice'
			);
			$timestamp = time();
			$emailLogs = array();
			foreach( $configs as $configClass ) {
				$startTime = time();

				$importConfig = new $configClass;
				$importConfig->setDOMDocument( $DOMDocument );
				$importConfig->clearLogMessages();

				$importController = new ljImportController( $importConfig );
				$importController->log( 'Starting import for ' . get_class( $importConfig ), array( 'blue' ) );
				$importController->run( $timestamp );

				$executionTime = round( microtime( true ) - $startTime, 2 );

				$importController->log( 'Import took ' . $executionTime . ' secs.' );
				$importController->log( 'Created ' . $importController->counter['create'] . ' items, updated ' . $importController->counter['update'] . ' items, skiped ' . $importController->counter['skip'] . ' items.' );
				$importController->log( 'Available items in feed: ' . count( $importController->config->dataList ) . '.' );

				if( $importController->counter['create'] + $importController->counter['update'] > 0) {
					$speed = ( $importController->counter['create'] + $importController->counter['update'] ) / $executionTime;
					$speed = round( $speed, 2 );
					$importController->log( 'Average speed: ' . $speed . ' items/sec.' );
				}

				$emailLogs[ str_replace( 'ljImportConfig', '', $configClass ) ] = $importConfig->getLogMessages();

				unset( $importController );
			}

			$emailLogs = ljImportController::groupLogMessages( $emailLogs );
			ljImportController::sendResultsEmail( $emailLogs );
		}
*/
		$result['collection']['status'] = 'SUCCESS';
		return $this->result( $result );
	}

	protected function result( $feed, $status = 200 ) {
		$result = new ezpRestMvcResult();
		$result->variables['feed']   = $feed;
		$result->variables['status'] = $status;
		return $result;
	}
}

