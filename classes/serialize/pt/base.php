<?php

/**
 * @package ContentSync
 * @class   ContentSyncSerializePTBase
 * @author  Serhey Dolgushev <dolgushev.serhey@gmail.com>
 * @date    17 Jan 2014
 * */
class ContentSyncSerializePTBase extends ContentSyncSerializeBase {

    public static $classIdentifier   = null;
    public static $skipSyncAttribute = 'disable_content_sync';

    public function getObjectsToSync( eZContentObject $object, $versionNumber = null, $language = null ) {
        $version = $versionNumber === null ? $object->attribute( 'current' ) : $object->version( $versionNumber );
        $dataMap = $version->attribute( 'data_map' );
        if( isset( $dataMap[self::$skipSyncAttribute] ) ) {
            $skipAttr = $dataMap[self::$skipSyncAttribute];
            if( (bool) $skipAttr->attribute( 'content' ) ) {
                return array();
            }
        }

        return parent::getObjectsToSync( $object, $versionNumber, $language );
    }

    public static function createLocationNode( $doc, $type, $uniqueID = null ) {
        $location = $doc->createElement( 'location' );
        $location->setAttribute( 'type', $type );
        if( $uniqueID !== null ) {
            $location->setAttribute( 'unique_id', $uniqueID );
        }
        return $location;
    }

    public static function getImageNode( DOMDocument $doc, eZContentObject $image = null, $versionNumber = null ) {
        $node = $doc->createElement( 'image' );
        if( $image instanceof eZContentObject === false ) {
            return $node;
        }

        if( $image->attribute( 'class_identifier' ) != ContentSyncSerializeImage::$classIdentifier ) {
            return $node;
        }

        if( $versionNumber === null ) {
            $dataMap = $image->attribute( 'data_map' );
        } else {
            $version = $image->version( $versionNumber );
            if( $version instanceof eZContentObjectVersion === false ) {
                return $node;
            }
            $dataMap = $version->attribute( 'data_map' );
        }
        $attr = self::createAttributeNode( $doc, 'name', $dataMap['name']->toString() );
        $node->appendChild( $attr );
        $attr = self::createAttributeNode( $doc, 'caption', $dataMap['caption']->toString() );
        $node->appendChild( $attr );

        $img = $dataMap['image']->attribute( 'content' );
        $node->appendChild( self::getImageFileNode( $doc, $img ) );

        return $node;
    }

    public static function getImageFileNode( DOMDocument $doc, eZImageAliasHandler $img ) {
        $node = $doc->createElement( 'image_file' );
        if( $img->attribute( 'is_valid' ) === false ) {
            return $node;
        }

        $original = $img->attribute( 'original' );
        $file     = $original['url'];
        // Fetch image if file storage is clustered
        eZClusterFileHandler::instance( $file )->fetch();
        if(
            file_exists( $file ) === false || (int) @filesize( $file ) === 0
        ) {
            return $node;
        }

        $attr = $doc->createElement( 'original_filename' );
        $attr->appendChild( $doc->createCDATASection( $img->attribute( 'original_filename' ) ) );
        $node->appendChild( $attr );

        $attr = $doc->createElement( 'file_hash' );
        $attr->appendChild( $doc->createCDATASection( hash_file( 'md5', $file ) ) );
        $node->appendChild( $attr );

        $fileURI = $file;
        eZURI::transformURI( $fileURI, true, 'full' );
        $attr    = $doc->createElement( 'file' );
        $attr->appendChild( $doc->createCDATASection( $fileURI ) );
        $node->appendChild( $attr );

        return $node;
    }

    public static function getTagsNode( DOMDocument $doc, eZContentObjectAttribute $attribute, $language = null ) {
        $node = $doc->createElement( 'tags' );
        $tags = $attribute->attribute( 'content' )->attribute( 'tags' );

        foreach( $tags as $k => $tag ) {
            $tagsPath = array();
            while( $tag instanceof eZTagsObject ) {
                $tagsPath[] = $tag;

                $tag = $tag->attribute( 'parent' );
            }

            if( count( $tagsPath ) === 0 ) {
                continue;
            }

            $tagsPath = array_reverse( $tagsPath );
            $tag      = $tags[$k];
            $link     = eZTagsAttributeLinkObject::fetchByObjectAttributeAndKeywordID(
                    $attribute->attribute( 'id' ), $attribute->attribute( 'version' ), $attribute->attribute( 'contentobject_id' ), $tag->attribute( 'id' )
            );

            $tagDataElement = $doc->createElement( 'tag-data' );
            $tagDataElement->setAttribute( 'remote_id', $tag->attribute( 'remote_id' ) );
            $tagDataElement->setAttribute( 'priority', $link instanceof eZTagsAttributeLinkObject ? $link->attribute( 'priority' ) : 0  );

            $tagPathElement = $doc->createElement( 'path' );
            $tagDataElement->appendChild( $tagPathElement );
            foreach( $tagsPath as $tag ) {
                $tagElement = $doc->createElement( 'tag' );
                $tagElement->setAttribute( 'remote_id', $tag->attribute( 'remote_id' ) );
                $tagElement->setAttribute( 'depth', $tag->attribute( 'depth' ) );

                $language = eZContentLanguage::fetch( $tag->attribute( 'main_language_id' ) );
                $tagElement->setAttribute( 'main_language', $language instanceof eZContentLanguage ? $language->attribute( 'locale' ) : null  );

                $languages = eZContentLanguage::decodeLanguageMask( $tag->attribute( 'language_mask' ), true );
                $tagElement->setAttribute( 'always_available', (int) $languages['always_available'] );
                $tagElement->setAttribute( 'available_languages', implode( ',', $languages['language_list'] ) );

                $tagKeywordElement = $doc->createElement( 'keyword' );
                $tagKeywordElement->appendChild( $doc->createCDATASection( $tag->Keyword ) );
                $tagElement->appendChild( $tagKeywordElement );

                $mainTag = $tag->attribute( 'main_tag' );
                $tagElement->setAttribute( 'main_tag_remote_id', $mainTag instanceof eZTagsObject ? $mainTag->attribute( 'remote_id' ) : 0  );

                $translations           = $tag->attribute( 'translations' );
                $tagTranslationsElement = $doc->createElement( 'translations' );
                foreach( $translations as $translation ) {
                    $tagTranslationElement = $doc->createElement( 'translation' );
                    $tagTranslationElement->setAttribute( 'locale', $translation->attribute( 'locale' ) );
                    $tagTranslationElement->appendChild( $doc->createCDATASection( $translation->attribute( 'keyword' ) ) );
                    $tagTranslationsElement->appendChild( $tagTranslationElement );
                }
                $tagElement->appendChild( $tagTranslationsElement );

                $tagPathElement->appendChild( $tagElement );
            }

            $node->appendChild( $tagDataElement );
        }

        return $node;
    }

    public static function createAttributeNode( DOMDocument $doc, $identifier, $value ) {
        $attr = $doc->createElement( 'attribute' );
        if(
            $value instanceof DOMElement === false && $value !== NULL
        ) {
            $value = $doc->createCDATASection( $value );
        }

        if( $value !== null ) {
            $attr->appendChild( $value );
        }

        $attr->setAttribute( 'identifier', $identifier );
        return $attr;
    }

    public static function fetchObjectDataMap( eZContentObject $object, eZContentObjectVersion $version ) {
        $dataMap = array();
        $data    = $version->fetchAttributes(
            $version->attribute( 'version' ), $object->attribute( 'id' ), self::getVersionLanguage( $version )
        );
        foreach( $data as $item ) {
            $dataMap[$item->contentClassAttributeIdentifier()] = $item;
        }

        return $dataMap;
    }

    public static function isMainTranslation( eZContentObject $object, eZContentObjectVersion $version ) {
        return $object->attribute( 'initial_language_id' ) == $version->attribute( 'initial_language_id' );
    }

    public function getRemoveObjectData( eZContentObject $object ) {
        $doc                     = new DOMDocument( '1.0', 'UTF-8' );
        $doc->formatOutput       = true;
        $doc->preserveWhiteSpace = false;

        $request = $doc->createElement( 'object' );
        $request->setAttribute( 'unique_id', static::getIdentifier( $object->attribute( 'current' ) ) );
        $request->setAttribute( 'type', static::$classIdentifier );
        $request->setAttribute( 'remove', 'yes' );
        $request->setAttribute( 'language', $object->attribute( 'default_language' ) );
        $doc->appendChild( $request );

        return $doc->saveXML();
    }

}
