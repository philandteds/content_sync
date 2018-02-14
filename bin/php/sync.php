<?php

/**
 * @package ContentSync
 * @author  Serhey Dolgushev <dolgushev.serhey@gmail.com>
 * @date    16 Jan 2014
 * */
ini_set( 'memory_limit', '1024M' );

function outputMemoryUsage( $cli ) {
    $memoryUsage = number_format( memory_get_usage( true ) / ( 1024 * 1024 ), 2 );
    $output      = 'Memory usage: ' . $memoryUsage . ' Mb';
    $cli->output( $output );
}

require 'autoload.php';
$cli = eZCLI::instance();
$cli->setUseStyles( true );

$scriptSettings                   = array();
$scriptSettings['description']    = 'Syncs all objects of specified content classes';
$scriptSettings['use-session']    = true;
$scriptSettings['use-modules']    = true;
$scriptSettings['use-extensions'] = true;

$script  = eZScript::instance( $scriptSettings );
$script->startup();
$options = $script->getOptions(
    '[class_identifiers:][offset:][limit:][use_main_language_only]', '', array(
    'class_identifiers'      => 'Content class identifiers, seprated by comma',
    'offset'                 => 'Fetch objects offset',
    'limit'                  => 'Fetch objects limit',
    'use_main_language_only' => 'Sync only main language translations'
    )
);
$script->initialize();

if( strlen( $options['class_identifiers'] ) === 0 ) {
    $cli->error( 'Please specify some content classes' );
    $script->shutdown( 1 );
}

$offset = $options['offset'] > 0 ? (int) $options['offset'] : false;
$limit  = $options['limit'] > 0 ? (int) $options['limit'] : false;

$events = eZWorkflowEvent::fetchFilteredList( array( 'workflow_type_string' => 'event_' . ContentSyncType::TYPE_ID ) );
if( count( $events ) === 0 ) {
    $cli->error( 'There is no "' . ContentSyncType::TYPE_ID . '" workflow event' );
    $script->shutdown( 1 );
}
$event = $events[0];

// Login as creator user
$userID = eZINI::instance()->variable( 'UserSettings', 'UserCreatorID' );
$user   = eZUser::fetch( $userID );
if( ( $user instanceof eZUser ) === false ) {
    $cli->error( 'Can not get user object by ID = "' . $userCreatorID . '"' );
    $script->shutdown( 1 );
}
eZUser::setCurrentlyLoggedInUser( $user, $userID );

$mainLanguage = eZINI::instance( 'site.ini' )->variable( 'RegionalSettings', 'Locale' );

$outputSeparator = str_repeat( '-', 80 );
$startTime       = microtime( true );

$clasIdentifiers = explode( ',', $options['class_identifiers'] );
foreach( $clasIdentifiers as $clasIdentifier ) {
    $class = eZContentClass::fetchByIdentifier( $clasIdentifier );
    if( $class instanceof eZContentClass === false ) {
        $cli->error( '"' . $clasIdentifier . '" is not valid class identifier' );
        continue;
    }

    $nodes      = eZContentObjectTreeNode::subTreeByNodeID(
            array(
            'Depth'            => false,
            'Limitation'       => array(),
            'LoadDataMap'      => false,
            'AsObject'         => false,
            'IgnoreVisibility' => true,
            'MainNodeOnly'     => true,
            'ClassFilterType'  => 'include',
            'ClassFilterArray' => array( $clasIdentifier ),
            'SortBy'           => array(
                array( 'depth', true )
            ),
            'Limit'            => $limit,
            'Offset'           => $offset
            ), 1
    );
    $totalCount = count( $nodes );
    $cli->output( $outputSeparator );
    $cli->output( '[' . date( 'c' ) . '] Starting content synchornization for "' . $clasIdentifier . '" class objects (' . $totalCount . ')...' );
    outputMemoryUsage( $cli );
    $cli->output( $outputSeparator );

    $i       = 1;
    $objects = eZPersistentObject::fetchObjectList(
            eZContentObject::definition(), null, array(
            'contentclass_id' => $class->attribute( 'id' ),
            'status'          => eZContentObject::STATUS_PUBLISHED
            )
    );

    foreach( $nodes as $node ) {
        if( $i % 10 === 0 ) {
            $progress = number_format( $i / $totalCount * 100, 2 );
            $cli->output( '[' . date( 'c' ) . '] ' . $progress . '% (' . $i . '/' . $totalCount . ')' );
            outputMemoryUsage( $cli );
        }
        $i++;

        $object = eZContentObject::fetch( $node['contentobject_id'] );
        if( $object instanceof eZContentObject === false ) {
            continue;
        }

        $languages     = $object->attribute( 'current' )->translations( false );
        $versionNumber = $object->attribute( 'current_version' );
        foreach( $languages as $language ) {
            if( $options['use_main_language_only'] && $mainLanguage !== $language ) {
                continue;
            }

            $objectsToSync = ContentSyncType::getObjectsToSync( $object, $versionNumber, $language );
            foreach( $objectsToSync as $info ) {
                ContentSyncType::requestContentSync( $info['object'], $info['version'], $events[0] );
            }
        }
    }
}

$cli->output( 'Content synchronization is complete.' );
$cli->output( 'Script run-time: ' . round( microtime( true ) - $startTime, 2 ) . ' seconds' );
$script->shutdown( 0 );
