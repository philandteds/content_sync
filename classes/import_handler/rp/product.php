<?php
/**
 * @package ContentSync
 * @class   ContentSyncImportHandlerXrowProduct
 * @author  Serhey Dolgushev <dolgushev.serhey@gmail.com>
 * @date    20 Jan 2014
 **/

class ContentSyncImportHandlerXrowProduct extends ContentSyncImportHandlereRPBase
{
	protected static $rootNodeURLPath       = 'restricted_products';
	protected static $imageContainerURLPath = 'files/images/products';
	protected static $simpleAttributes      = array(
		'name',
		'product_id',
		'version',
		'product_name',
		'xrow_prod_desc',
		'short_description',
		'description',
		'video',
		'hide_product',
		'tags',
		'brand',
		'display_in_websites',
		'parent_category_identifiers'
	);

	protected $filesToRemoveAfterPublish = array();

	public static function fetchNode( $uniqueID ) {
		$parts       = explode( '|', $uniqueID );
		$class       = ContentSyncSerializeXrowProduct::$classIdentifier;
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
				array( $class . '/product_id', '=', $parts[0] ),
				array( $class . '/version', '=', $parts[1] )
			)
		);

		$nodes = eZContentObjectTreeNode::subTreeByNodeID( $fetchParams, 1 );
		if( count( $nodes ) > 0 ) {
			return $nodes[0];
		}

		return null;
	}

	public function processAttributes( array $attributes, eZContentObjectVersion $existingVerion = null ) {
		$return = $this->processSimpleAttributes( $attributes );

		foreach( $attributes as $attribute ) {
			$identifier = (string) $attribute['identifier'];

			// Logo
			if( $identifier === 'xrow_logo' ) {
				$imageLocalFile = self::processImageAttribute( $attribute, $existingVerion );
				if( $imageLocalFile !== null ) {
					$this->filesToRemoveAfterPublish[] = $imageLocalFile;
					$return[ $identifier ] = $imageLocalFile;
				}
				continue;
			}

			// Shop homepage image
			if( $identifier === 'image' ) {
				$return[ $identifier ] = self::processRealtedImagesAttribute(
					$attribute,
					self::getImagesContainerNode(),
					$existingVerion
				);
				continue;
			}

			// Images
			if( $identifier === 'images' ) {
				$return[ $identifier ] = self::processRealtedImagesAttribute(
					$attribute,
					self::getImagesContainerNode(),
					$existingVerion
				);
				continue;
			}
		}

		return $return;
	}

	public function import( array $objectData, eZContentObjectVersion $existingVersion = null ) {
		$result = parent::import( $objectData, $existingVersion );

		foreach( $this->filesToRemoveAfterPublish as $file ) {
			@unlink( $file );
		}
		$this->filesToRemoveAfterPublish = array();

		return $result;
	}
}