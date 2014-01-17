<?php
/**
 * @package ContentSync
 * @class   ContentSyncSerializeProductCategory
 * @author  Serhey Dolgushev <dolgushev.serhey@gmail.com>
 * @date    17 Jan 2014
 **/

class ContentSyncSerializeProductCategory extends ContentSyncSerializeBase
{
	protected static $classIdentifier = 'product_category';

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
		$request->setAttribute( 'unqique_id', $identifier );
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
				$location->setAttribute( 'unqique_id', $lDataMap['identifier']->attribute( 'content' ) );
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
			$cdata = false;

			if( isset( $dataMap[ $attrIdentifier ] ) ) {
				$value = $dataMap[ $attrIdentifier ]->toString();
			}

			$attribute = $doc->createElement( 'attribute' );
			$attribute->appendChild( $doc->createCDATASection( $value ) );
			$attribute->setAttribute( 'identifier', $attrIdentifier );

			$attributes->appendChild( $attribute );
		}
		$request->appendChild( $attributes );

		return $doc->saveXML();
	}
}