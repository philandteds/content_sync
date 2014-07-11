<?php

/**
 * @package ContentSync
 * @class   ContentSyncSerializeProductCategory
 * @author  Serhey Dolgushev <dolgushev.serhey@gmail.com>
 * @date    17 Jan 2014
 * */
class ContentSyncSerializeProductCategory extends ContentSyncSerializePTBase {

    public static $classIdentifier = 'product_category';

    public function getObjectData( eZContentObject $object, eZContentObjectVersion $version ) {
        $dataMap    = self::fetchObjectDataMap( $object, $version );
        $nodes      = $object->assignedNodes();
        $language   = self::getVersionLanguage( $version );
        $identifier = $dataMap['identifier']->attribute( 'content' );

        $doc                     = new DOMDocument( '1.0', 'UTF-8' );
        $doc->formatOutput       = true;
        $doc->preserveWhiteSpace = false;

        // General object data
        $request = $doc->createElement( 'object' );
        $request->setAttribute( 'unique_id', $identifier );
        $request->setAttribute( 'language', $language );
        $request->setAttribute( 'is_main_translation', (int) self::isMainTranslation( $object, $version ) );
        $request->setAttribute( 'type', self::$classIdentifier );
        $doc->appendChild( $request );

        // Locations
        $locations         = $doc->createElement( 'locations' );
        $parentIdentifiers = trim( $dataMap['parent_category_identifiers']->toString() );
        if( strlen( $parentIdentifiers ) !== 0 ) {
            $parentIdentifiers = explode( ',', $parentIdentifiers );
            foreach( $parentIdentifiers as $parentIdentifier ) {
                $location = self::createLocationNode( $doc, self::$classIdentifier, trim( $parentIdentifier ) );
                $locations->appendChild( $location );
            }
        } else {
            foreach( $nodes as $node ) {
                $parent = $node->attribute( 'parent' );
                if( $parent->attribute( 'class_identifier' ) !== self::$classIdentifier ) {
                    $location = self::createLocationNode( $doc, 'root' );
                } else {
                    $lDataMap = $parent->attribute( 'data_map' );
                    $location = self::createLocationNode( $doc, self::$classIdentifier, $lDataMap['identifier']->attribute( 'content' ) );
                }

                $locations->appendChild( $location );
            }
        }
        $request->appendChild( $locations );

        // Content attributes
        $syncAttrs  = array(
            'name',
            'short_name',
            'description',
            'category_message',
            'identifier',
            'tags',
            'xrow_prod_desc',
            'show_in_main_menu',
            'show_in_products_menu',
            'parent_category_identifier'
        );
        $attributes = $doc->createElement( 'attributes' );
        foreach( $syncAttrs as $attrIdentifier ) {
            $value = null;
            if( isset( $dataMap[$attrIdentifier] ) ) {
                $value = $dataMap[$attrIdentifier]->toString();
            }

            $attributes->appendChild( self::createAttributeNode( $doc, $attrIdentifier, $value ) );
        }

        // Image
        $image = self::getImageNode( $doc, $dataMap['image']->attribute( 'content' ) );
        $attributes->appendChild( self::createAttributeNode( $doc, 'image', $image ) );

        $request->appendChild( $attributes );

        return $doc->saveXML();
    }

    public static function getIdentifier( eZContentObjectVersion $version ) {
        $dataMap = $version->attribute( 'data_map' );
        if( isset( $dataMap['identifier'] ) === false ) {
            return null;
        }

        return $dataMap['identifier']->attribute( 'content' );
    }

}
