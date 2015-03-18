<?php

/**
 * @package ContentSync
 * @class   ContentSyncImportHandlerUser
 * @author  Serhey Dolgushev <dolgushev.serhey@gmail.com>
 * @date    18 Mar 2015
 * */
class ContentSyncImportHandlerUser extends ContentSyncImportHandlereRPBase {

    protected static $rootNodeURLPath  = 'users/members';
    protected static $simpleAttributes = array(
        'first_name',
        'last_name',
        'user_account'
    );

    public static function fetchNode( $uniqueID ) {
        $user = eZUser::fetchByName( $uniqueID );
        return $user instanceof eZUser ? $user->attribute( 'contentobject' )->attribute( 'main_node' ) : null;
    }

    public function processAttributes( array $attributes, $uniqueID, eZContentObjectVersion $existingVerion = null ) {
        $return = $this->processSimpleAttributes( $attributes );

        return $return;
    }

    public function processLocations( array $locations, array $objectData ) {
        $root = eZContentObjectTreeNode::fetchByURLPath( static::$rootNodeURLPath );
        if( $root instanceof eZContentObjectTreeNode === false ) {
            $message = 'Root node with URL path "' . static::$rootNodeURLPath . '" is missing';
            ContentSyncImport::addLogtMessage( $message );
        }

        return array( $root );
    }

    public function import( array $objectData, eZContentObjectVersion $existingVersion = null ) {
        $object = $this->fetchObject( $objectData['unique_id'] );

        if( $object instanceof eZContentObject === false ) {
            return $this->createObject( $objectData );
        } else {
            $objectData['locations'] = array( $object->attribute( 'main_node' )->attribute( 'parent' ) );
            return $this->updateObject( $objectData, $object, $existingVersion );
        }
    }

}
