<?php
/**
 * @package ContentSync
 * @author  Serhey Dolgushev <dolgushev.serhey@gmail.com>
 * @date    16 Jan 2014
 **/

ini_set( 'memory_limit', '256M' );

require 'autoload.php';
$cli = eZCLI::instance();
$cli->setUseStyles( true );

$scriptSettings = array();
$scriptSettings['description']    = 'Imports content object data';
$scriptSettings['use-session']    = true;
$scriptSettings['use-modules']    = true;
$scriptSettings['use-extensions'] = true;

$script = eZScript::instance( $scriptSettings );
$script->startup();
$script->initialize();
$options = $script->getOptions(
	'[object_data_file:][creator_id:]',
	'',
	array(
		'object_data_file' => 'Source language',
		'creator_id'       => 'Target language',
	)
);

// Check parameters
$params = array(
	'object_data_file' => null,
	'creator_id'       => null
);
foreach( $params as $key => $value ) {
	if( $options[ $key ] === null ) {
		$cli->error( 'Please specify "' . $key . '" parameter' );
		$script->shutdown( 1 );
	}

	$params[ $key ] = $options[ $key ];
}

// Login as creator user
$user = eZUser::fetch( $params['creator_id'] );
if( ( $user instanceof eZUser ) === false ) {
	$cli->error( 'Can not get user object by ID = "' . $userCreatorID . '"' );
	$script->shutdown( 1 );
}
eZUser::setCurrentlyLoggedInUser( $user, $params['creator_id'] );

// Retrieve XML from file and check it
$cli->output( '[' . date( 'c' ) . '] Processing "' . $params['object_data_file'] . '"' );

if( file_exists( $params['object_data_file'] ) === false ) {
	$cli->error( '"' . $params['object_data_file'] . '" does not exist' );
	$script->shutdown( 1 );
}
$xml = file_get_contents( $params['object_data_file'] );

$DOMDocument = new DOMDocument();
if( @$DOMDocument->loadXML( $xml ) === false ) {
	$cli->error( '"' . $params['object_data_file'] . '" is not valid XML file' );
	$script->shutdown( 1 );
}

// Handle object data
try{
	ContentSyncController::handleObjectData( $xml );
} catch( Exception $e ) {
	$cli->error( $e->getMessage() );
	$script->shutdown( 1 );
}

$cli->output( '"' . $params['object_data_file'] . '" is processed' );
$script->shutdown( 0 );
