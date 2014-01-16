<?php
/**
 * @package ContentSync
 * @author  Serhey Dolgushev <dolgushev.serhey@gmail.com>
 * @date    15 Jan 2014
 **/

$module = $Params['Module'];
$object = eZContentObject::fetch( $Params['ObjectID'] );
if ( $object instanceof eZContentObject === false ) {
	return $module->handleError( eZError::KERNEL_NOT_AVAILABLE, 'kernel' );
}

$version = $object->version( $Params['Version'] );
if ( $version instanceof eZContentObjectVersion === false ) {
	return $module->handleError( eZError::KERNEL_NOT_AVAILABLE, 'kernel' );
}

$events = eZWorkflowEvent::fetchFilteredList( array( 'workflow_type_string' => 'event_' . ContentSyncType::TYPE_ID ) );
if( count( $events ) === 0 ) {
	return $module->handleError( eZError::KERNEL_NOT_AVAILABLE, 'kernel' );
}
ContentSyncType::requestContentSync( $object, $version, $events[0] );

return $module->redirectToView(
	'request_logs',
	array(),
	array( 'NewCreated' => 1 )
);