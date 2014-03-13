<?php

/**
 * @package ContentSync
 * @class   ContentSyncSerializeImage
 * @author  Serhey Dolgushev <dolgushev.serhey@gmail.com>
 * @date    18 Jan 2014
 * */
class ContentSyncSerializeImage extends ContentSyncSerializePTBase {

    public static $classIdentifier = 'image';

    public function getObjectsToSync( eZContentObject $object, $versionNumber = null, $language = null ) {
        if( $versionNumber === null ) {
            $versionNumber = $object->attribute( 'current_version' );
        }

        $params         = array(
            'AllRelations' => eZContentObject::RELATION_ATTRIBUTE
        );
        $relatedObjects = $object->reverseRelatedObjectList( $versionNumber, 0, false, $params );
        $objectsData    = array();
        foreach( $relatedObjects as $relatedObject ) {
            if(
                $relatedObject->attribute( 'class_identifier' ) == ContentSyncSerializeProductCategory::$classIdentifier
                || $relatedObject->attribute( 'class_identifier' ) == ContentSyncSerializeXrowProduct::$classIdentifier
            ) {
                $dataMap = $relatedObject->attribute( 'data_map' );
                if( isset( $dataMap[self::$skipSyncAttribute] ) ) {
                    $skipAttr = $dataMap[self::$skipSyncAttribute];
                    if( (bool) $skipAttr->attribute( 'content' ) ) {
                        continue;
                    }
                }

                $objectsData[] = array(
                    'object'  => $relatedObject,
                    'version' => $relatedObject->attribute( 'current' )
                );
            }
        }

        return $objectsData;
    }

    public function getRemoveObjectData( eZContentObject $object ) {
        return null;
    }

}
