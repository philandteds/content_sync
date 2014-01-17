<?php
/**
 * @package ContentSync
 * @author  Serhey Dolgushev <dolgushev.serhey@gmail.com>
 * @date    16 Jan 2014
 **/

$params = $Params['Module']->UserParameters;
$offset = isset( $params['offset'] ) ? (int) $params['offset'] : 0;
$limit  = isset( $params['limit'] ) ? (int) $params['limit'] : 50;

$limitations = array(
	'limit'  => $limit,
	'offset' => $offset
);
$logs = ContentSyncLogImport::fetchList( null, $limitations );

$tpl = eZTemplate::factory();
$tpl->setVariable( 'offset', $offset );
$tpl->setVariable( 'limit', $limit );
$tpl->setVariable( 'total_count', ContentSyncLogImport::countAll() );
$tpl->setVariable( 'logs', $logs );

$Result = array();
$Result['navigation_part'] = eZINI::instance( 'content_sync.ini' )->variable( 'NavigationParts', 'ImportLogs' );
$Result['content']         = $tpl->fetch( 'design:content_sync/import_logs/list.tpl' );
$Result['path']            = array(
	array(
		'text' => ezpI18n::tr( 'extension/content_sync', 'Content synchronization' ),
		'url'  => 'content_sync/import_logs'
	),
	array(
		'text' => ezpI18n::tr( 'extension/content_sync', 'Import logs' ),
		'url'  => false
	)
);