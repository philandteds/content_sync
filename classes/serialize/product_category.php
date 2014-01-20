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
		$locations = $doc->createElement( 'locations' );
		foreach( $nodes as $node ) {
			$parent   = $node->attribute( 'parent' );
			$location = $doc->createElement( 'location' );
			if( $parent->attribute( 'class_identifier' ) !== self::$classIdentifier ) {
				$location->setAttribute( 'type', 'root' );
			} else {
				$lDataMap = $parent->attribute( 'data_map' );
				$location->setAttribute( 'type', self::$classIdentifier );
				$location->setAttribute( 'unique_id', $lDataMap['identifier']->attribute( 'content' ) );
			}

			$locations->appendChild( $location );
		}
		$request->appendChild( $locations );

		// Content attributes
		$syncAttrs  = array(
			'name',
			'short_name',
			'description',
			'category_message',
			'identifier',
			'seo_title',
			'seo_description',
			'tags',
			'xrow_prod_desc',
			'show_in_main_menu',
			'show_in_products_menu'
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

		$attr = self::createAttributeNode( $doc, 'original_filename', $img->attribute( 'original_filename' ) );
		$node->appendChild( $attr );

		$attr = self::createAttributeNode( $doc, 'file_hash', hash_file( 'md5', $file ) );
		$node->appendChild( $attr );

		$fileURI = $file;
		eZURI::transformURI( $fileURI, true, 'full' );
		$attr = self::createAttributeNode( $doc, 'file', $fileURI );
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