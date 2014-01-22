<?php
/**
 * @package ContentSync
 * @class   ContentSyncSerializePTBase
 * @author  Serhey Dolgushev <dolgushev.serhey@gmail.com>
 * @date    17 Jan 2014
 **/

class ContentSyncSerializePTBase extends ContentSyncSerializeBase
{
	public static $classIdentifier = null;

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
			file_exists( $file ) === false
			|| (int) @filesize( $file ) === 0
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
		$attr = $doc->createElement( 'file' );
		$attr->appendChild( $doc->createCDATASection( $fileURI ) );
		$node->appendChild( $attr );

		return $node;
	}

	public static function createAttributeNode( DOMDocument $doc, $identifier, $value ) {
		$attr = $doc->createElement( 'attribute' );
		if(
			$value instanceof DOMElement === false
			&& $value !== NULL
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
			$version->attribute( 'version' ),
			$object->attribute( 'id' ),
			$version->initialLanguageCode()
		);
		foreach( $data as $item ) {
			$dataMap[ $item->contentClassAttributeIdentifier() ] = $item;
		}

		return $dataMap;
	}
}