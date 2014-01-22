<?php
/**
 * @package ContentSync
 * @class   ContentSyncSerializeProductCategory
 * @author  Serhey Dolgushev <dolgushev.serhey@gmail.com>
 * @date    17 Jan 2014
 **/

class ContentSyncSerializeProductCategory extends ContentSyncSerializeBase
{
	public static $classIdentifier = 'product_category';

	public function getObjectData( eZContentObject $object, eZContentObjectVersion $version ) {
		$dataMap    = $version->attribute( 'data_map' );
		$nodes      = $object->assignedNodes();
		$language   = $version->attribute( 'initial_language' )->attribute( 'locale' );
		$identifier = $dataMap['identifier']->attribute( 'content' );

		$doc = new DOMDocument( '1.0', 'UTF-8' );
		$doc->formatOutput       = true;
		$doc->preserveWhiteSpace = false;

		// General object data
		$request = $doc->createElement( 'object' );
		$request->setAttribute( 'unique_id', $identifier );
		$request->setAttribute( 'language', $language );
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
				$parent   = $node->attribute( 'parent' );
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
			if( isset( $dataMap[ $attrIdentifier ] ) ) {
				$value = $dataMap[ $attrIdentifier ]->toString();
			}

			$attributes->appendChild( self::createAttributeNode( $doc, $attrIdentifier, $value ) );
		}

		// Image
		$image = self::getImageNode( $doc, $dataMap['image']->attribute( 'content' ) );
		$attributes->appendChild( self::createAttributeNode( $doc, 'image', $image ) );

		$request->appendChild( $attributes );

		return $doc->saveXML();
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
}