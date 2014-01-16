<?php
/**
 * @package ContentSync
 * @class   ContentSyncType
 * @author  Serhey Dolgushev <dolgushev.serhey@gmail.com>
 * @date    09 Jan 2014
 **/

$Module = array(
	'name'            => 'Content synchronization',
 	'variable_params' => true
);

$ViewList = array(
	'send_request' => array(
		'functions' => array( 'sync' ),
		'script'    => 'send_request.php',
		'params'    => array( 'ObjectID', 'Version' )
	),
	'request_logs' => array(
		'functions'        => array( 'logs' ),
		'script'           => 'request_logs.php',
		'params'           => array(),
		'unordered_params' => array( 'NewCreated' ),
	),
	'request_details' => array(
		'functions' => array( 'logs' ),
		'script'    => 'request_details.php',
		'params'    => array( 'ID' )
	),
	'import_logs' => array(
		'functions' => array( 'logs' ),
		'script'    => 'log/import.php',
		'params'    => array()
	)
);

$FunctionList = array(
	'sync' => array(),
	'logs' => array()
);
