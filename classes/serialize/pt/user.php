<?php

/**
 * @package ContentSync
 * @class   ContentSyncSerializeUser
 * @author  Serhey Dolgushev <dolgushev.serhey@gmail.com>
 * @date    18 Mar 2015
 * */
class ContentSyncSerializeUser extends ContentSyncSerializePTBase {

    public static $classIdentifier = 'user';

    public function getObjectsToSync( eZContentObject $object, $versionNumber = null, $language = null ) {
        return parent::getObjectsToSync( $object, $versionNumber, $language );
    }

    public function getObjectData( eZContentObject $object, eZContentObjectVersion $version ) {
        $dataMap  = self::fetchObjectDataMap( $object, $version );
        $language = self::getVersionLanguage( $version );

        $account = $dataMap['user_account']->attribute( 'content' );
        $login   = $account->attribute( 'login' );

        $doc                     = new DOMDocument( '1.0', 'UTF-8' );
        $doc->formatOutput       = true;
        $doc->preserveWhiteSpace = false;

        // General object data
        $request = $doc->createElement( 'object' );
        $request->setAttribute( 'unique_id', $login );
        $request->setAttribute( 'language', $language );
        $request->setAttribute( 'is_main_translation', (int) self::isMainTranslation( $object, $version ) );
        $request->setAttribute( 'type', self::$classIdentifier );
        $doc->appendChild( $request );

        // On the RP we are updating existing users and creating new ones under Members group
        // thats why locations data is not present in the sync request
        $locations = $doc->createElement( 'locations' );
        $request->appendChild( $locations );

        // Content attributes
        $syncAttrs  = array(
            'first_name',
            'last_name',
            'user_account'
        );
        $attributes = $doc->createElement( 'attributes' );
        foreach( $syncAttrs as $attrIdentifier ) {
            $value = null;
            if( isset( $dataMap[$attrIdentifier] ) ) {
                $value = $dataMap[$attrIdentifier]->toString();
            }

            $attributes->appendChild( self::createAttributeNode( $doc, $attrIdentifier, $value ) );
        }

        $request->appendChild( $attributes );

        return $doc->saveXML();
    }

    public function getRemoveObjectData( eZContentObject $object ) {
        return null;
    }

}
