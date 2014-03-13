<?php

/**
 * @package ContentSync
 * @author  Serhey Dolgushev <dolgushev.serhey@gmail.com>
 * @date    13 Jan 2014
 * */
$module = $Params['Module'];
$log    = ContentSyncLogRequest::fetch( (int) $module->NamedParameters['ID'] );
if( $log instanceof ContentSyncLogRequest === false ) {
    return $module->handleError( eZError::KERNEL_NOT_FOUND, 'kernel' );
}

$tpl = eZTemplate::factory();
$tpl->setVariable( 'log', $log );

$Result                    = array();
$Result['navigation_part'] = eZINI::instance( 'content_sync.ini' )->variable( 'NavigationParts', 'RequestLogs' );
$Result['content']         = $tpl->fetch( 'design:content_sync/request_logs/detailds.tpl' );
$Result['path']            = array(
    array(
        'text' => ezpI18n::tr( 'extension/content_sync', 'Request logs' ),
        'url'  => 'content_sync/request_logs'
    ),
    array(
        'text' => ezpI18n::tr( 'extension/content_sync', 'Request details' ),
        'url'  => false
    )
);
