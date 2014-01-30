<?php
/**
 * @package ContentSync
 * @class   ContentSyncImportHandlerProductCategory
 * @author  Serhey Dolgushev <dolgushev.serhey@gmail.com>
 * @date    20 Jan 2014
 **/

class ContentSyncImportHandlerProductCategory extends ContentSyncImportHandlereRPBase
{
	protected static $rootNodeURLPath       = 'buy';
	protected static $imageContainerURLPath = 'files/images/product_categories';
	protected static $simpleAttributes      = array(
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

	public static function fetchNode( $uniqueID ) {
		$class       = ContentSyncSerializeProductCategory::$classIdentifier;
		$fetchParams = array(
			'Depth'            => false,
			'Limitation'       => array(),
			'LoadDataMap'      => false,
			'AsObject'         => true,
			'IgnoreVisibility' => true,
			'MainNodeOnly'     => true,
			'ClassFilterType'  => 'include',
			'ClassFilterArray' => array( $class ),
			'AttributeFilter'  => array(
				array( $class . '/identifier', '=', $uniqueID )
			)
		);

		$nodes = eZContentObjectTreeNode::subTreeByNodeID( $fetchParams, 1 );
		if( count( $nodes ) > 0 ) {
			return $nodes[0];
		}

		return null;
	}

	public function processAttributes( array $attributes, $uniqueID, eZContentObjectVersion $existingVerion = null ) {
		$return = $this->processSimpleAttributes( $attributes );
		$object = $this->fetchObject( $uniqueID );

		// Image attriubte
		foreach( $attributes as $attribute ) {
			$identifier = (string) $attribute['identifier'];
			if( $identifier === 'image' ) {
				$return[ $identifier ] = self::processRealtedImagesAttribute(
					$attribute,
					self::getImagesContainerNode(),
					$object
				);
				break;
			}
		}

		return $return;
	}
}