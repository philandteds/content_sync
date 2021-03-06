<?php

/**
 * @package ContentSync
 * @author  Serhey Dolgushev <dolgushev.serhey@gmail.com>
 * @date    16 Jan 2014
 * */
$module       = $Params['Module'];
$params       = $Params['Module']->UserParameters;
$http         = eZHTTPTool::instance();
$filterObject = null;

if( $module->isCurrentAction( 'BrowseFilterObject' ) ) {
    $browseParameters = array(
        'action_name' => 'SetFilterObject',
        'type'        => 'AddRelatedObjectToDataType',
        'from_page'   => 'content_sync/import_logs'
    );
    return eZContentBrowse::browse( $browseParameters, $Params['Module'] );
} elseif( $module->isCurrentAction( 'SetFilterObject' ) ) {
    $objectIDs    = (array) $http->variable( 'SelectedObjectIDArray' );
    $filterObject = eZContentObject::fetch( $objectIDs[0] );
}

if(
    $filterObject === null && isset( $params['filter_object_id'] ) && (int) $params['filter_object_id'] > 0
 ) {
    $filterObject = eZContentObject::fetch( $params['filter_object_id'] );
}

$offset = isset( $params['offset'] ) ? (int) $params['offset'] : 0;
$limit  = isset( $params['limit'] ) ? (int) $params['limit'] : 50;

$conditions = null;
if( $filterObject instanceof eZContentObject ) {
    $conditions = array( 'object_id' => $filterObject->attribute( 'id' ) );
}

$limitations = array(
    'limit'  => $limit,
    'offset' => $offset
);
$logs        = ContentSyncLogImport::fetchList( $conditions, $limitations );

$tpl = eZTemplate::factory();
$tpl->setVariable( 'filter_object', $filterObject );
$tpl->setVariable( 'offset', $offset );
$tpl->setVariable( 'limit', $limit );
$tpl->setVariable( 'total_count', ContentSyncLogImport::countAll( $conditions ) );
$tpl->setVariable( 'logs', $logs );

$Result                    = array();
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
