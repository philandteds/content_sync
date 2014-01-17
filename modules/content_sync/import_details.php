<?php
/**
 * @package ContentSync
 * @author  Serhey Dolgushev <dolgushev.serhey@gmail.com>
 * @date    16 Jan 2014
 **/

$module = $Params['Module'];
$log    = ContentSyncLogImport::fetch( (int) $module->NamedParameters['ID'] );
if( $log instanceof ContentSyncLogImport === false ) {
	return $module->handleError( eZError::KERNEL_NOT_FOUND, 'kernel' );
}

$tpl = eZTemplate::factory();
$tpl->setVariable( 'log', $log );

$Result = array();
$Result['navigation_part'] = eZINI::instance( 'content_sync.ini' )->variable( 'NavigationParts', 'ImportLogs' );
$Result['content']         = $tpl->fetch( 'design:content_sync/import_logs/detailds.tpl' );
$Result['path']            = array(
	array(
		'text' => ezpI18n::tr( 'extension/content_sync', 'Import logs' ),
		'url'  => 'content_sync/import_logs'
	),
	array(
		'text' => ezpI18n::tr( 'extension/content_sync', 'Import details' ),
		'url'  => false
	)
);