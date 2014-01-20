<?php
/**
 * @package ContentSync
 * @class   ContentSyncSerializeXrowProduct
 * @author  Serhey Dolgushev <dolgushev.serhey@gmail.com>
 * @date    17 Jan 2014
 **/

class ContentSyncSerializeXrowProduct extends ContentSyncSerializeBase
{
	public static $classIdentifier = 'xrow_product';

	public function getObjectData( eZContentObject $object, eZContentObjectVersion $version ) {
		$dataMap    = $version->attribute( 'data_map' );
		$nodes      = $object->assignedNodes();
		$language   = $version->attribute( 'initial_language' )->attribute( 'locale' );

		$doc = new DOMDocument( '1.0', 'UTF-8' );
		$doc->formatOutput       = true;
		$doc->preserveWhiteSpace = false;

		// General object data
		$request = $doc->createElement( 'object' );
		$request->setAttribute( 'unique_id', self::getIdentifier( $version ) );
		$request->setAttribute( 'language', $language );
		$request->setAttribute( 'type', self::$classIdentifier );
		$doc->appendChild( $request );

		// Locations
		$locations = $doc->createElement( 'locations' );
		foreach( $nodes as $node ) {
			$parent   = $node->attribute( 'parent' );
			$location = $doc->createElement( 'location' );
			if( $parent->attribute( 'class_identifier' ) !== ContentSyncSerializeProductCategory::$classIdentifier ) {
				// Restricted products folder
				$location->setAttribute( 'type', 'root' );
			} else {
				$lDataMap = $parent->attribute( 'data_map' );
				$location->setAttribute( 'type', ContentSyncSerializeProductCategory::$classIdentifier );
				$location->setAttribute( 'unique_id', $lDataMap['identifier']->attribute( 'content' ) );
			}

			$locations->appendChild( $location );
		}
		$request->appendChild( $locations );

		// Content attributes
		$syncAttrs  = array(
			'name',
			'product_id',
			'version',
			'product_name',
			'xrow_prod_desc',
			'short_description',
			'description',
			'video',
			'hide_product',
			'tags'
		);
		$attributes = $doc->createElement( 'attributes' );
		foreach( $syncAttrs as $attrIdentifier ) {
			$value = null;
			if( isset( $dataMap[ $attrIdentifier ] ) ) {
				$value = $dataMap[ $attrIdentifier ]->toString();
			}

			$attributes->appendChild( ContentSyncSerializeProductCategory::createAttributeNode( $doc, $attrIdentifier, $value ) );
		}

		// Logo
		$image = ContentSyncSerializeProductCategory::getImageFileNode( $doc, $dataMap['xrow_logo']->attribute( 'content' ) );
		$attributes->appendChild( ContentSyncSerializeProductCategory::createAttributeNode( $doc, 'xrow_logo', $image ) );
		// Shop homepage image
		$image = ContentSyncSerializeProductCategory::getImageNode( $doc, $dataMap['image']->attribute( 'content' ) );
		$attributes->appendChild( ContentSyncSerializeProductCategory::createAttributeNode( $doc, 'image', $image ) );
		// Images
		$images        = ContentSyncSerializeProductCategory::createAttributeNode( $doc, 'images', null );
		$relatedImages = $dataMap['images']->attribute( 'content' );
		foreach( $relatedImages['relation_list'] as $relation ) {
			$image = eZContentObject::fetch( $relation['contentobject_id'] );
			if( $image instanceof eZContentObject === false ) {
				continue;
			}
			$images->appendChild( ContentSyncSerializeProductCategory::getImageNode( $doc, $image, $relation['contentobject_version'] ) );
		}
		$attributes->appendChild( $images );

		// Required related products
		$requiredlProducts = self::getRelatedProductsNode( $doc, $dataMap['required_related_products'] );
		$attributes->appendChild( $requiredlProducts );

		// Optional related products
		$optionalProducts = self::getRelatedProductsNode( $doc, $dataMap['optional_related_products'] );
		$attributes->appendChild( $optionalProducts );

		// Colour image map
		$colourMap = self::getColourMapNode( $doc, $dataMap['colour_image_map'] );
		$attributes->appendChild( $colourMap );
/*
    Product page link
	$node = $object->attribute( 'main_node' );
		var_dump( $language );
		$node->setCurrentLanguage( $language );
		var_dump( $node->CurrentLanguage );
 */
		$request->appendChild( $attributes );

		return $doc->saveXML();
	}

	public static function getIdentifier( eZContentObjectVersion $version ) {
		$dataMap = $version->attribute( 'data_map' );
		return $dataMap['product_id']->attribute( 'content' ) . '|' . $dataMap['version']->attribute( 'content' );
	}

	protected static function getRelatedProductsNode( $doc, eZContentObjectAttribute $attribute ) {
		$identifier       = $attribute->attribute( 'contentclass_attribute_identifier' );
		$optionalProducts = ContentSyncSerializeProductCategory::createAttributeNode( $doc, $identifier, null );
		$relatedProducts  = $attribute->attribute( 'content' );
		foreach( $relatedProducts['relation_list'] as $relation ) {
			$relatedProduct = eZContentObject::fetch( $relation['contentobject_id'] );
			if(
				$relatedProduct instanceof eZContentObject === false
				|| $relatedProduct->attribute( 'class_identifier' ) != self::$classIdentifier
			) {
				continue;
			}

			$node = $doc->createElement( 'product' );
			$node->setAttribute( 'unique_id', self::getIdentifier( $relatedProduct->attribute( 'current' ) ) );
			$optionalProducts->appendChild( $node );
		}

		return $optionalProducts;
	}

	protected static function getColourMapNode( $doc, eZContentObjectAttribute $attribute ) {
		$identifier = $attribute->attribute( 'contentclass_attribute_identifier' );
		$colourMap  = ContentSyncSerializeProductCategory::createAttributeNode( $doc, $identifier, null );
		$colours    = $attribute->attribute( 'content' );
		foreach( $colours['main']['result'] as $SKUImage ) {
			$node  = $doc->createElement( 'sku_image' );
			$node->setAttribute( 'colour', $SKUImage['Colour'] );
			$node->setAttribute( 'css_colour', $SKUImage['CSSColour'] );

			$imageIDs = explode( ',', $SKUImage['Imageid'] );
			foreach( $imageIDs as $imageID ) {
				$image = eZContentObject::fetch( $imageID );
				if( $image instanceof eZContentObject === false ) {
					continue;
				}
				$imageNode = ContentSyncSerializeProductCategory::getImageNode( $doc, $image );
				$node->appendChild( $imageNode );
			}
			$colourMap->appendChild( $node );
		}

		return $colourMap;
	}
}