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

	public function fetchNode( $uniqueID ) {
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

	public function processAttributes( array $attributes, eZContentObjectVersion $existingVerion = null ) {
		$return = $this->processSimpleAttributes( $attributes );

		// Image attriubte
		foreach( $attributes as $attribute ) {
			$identifier = (string) $attribute['identifier'];
			if( $identifier === 'image' ) {
				$return[ $identifier ] = self::processRealtedImagesAttribute(
					$attribute,
					self::getImagesContainerNode(),
					$existingVerion
				);
				break;
			}
		}

		return $return;
	}

	public function import( array $objectData, eZContentObjectVersion $existingVersion = null ) {
		$result = array(
			'object_id'      => null,
			'object_version' => null,
			'status'         => ContentSyncLogImport::STATUS_SKIPPED
		);

		$publishParams = array(
			'class_identifier' => $objectData['type'],
			'attributes'       => $objectData['attributes'],
			'language'         => $objectData['language']
		);

		$object = $this->fetchObject( $objectData['unique_id'] );
		if( $object instanceof eZContentObject === false ) {
			$mainParent        = $objectData['locations'][0];
			$additionalParents = array_slice( $objectData['locations'], 1 );

			$publishParams['parent_node_id'] = $mainParent->attribute( 'node_id' );
			$object = ContentSyncContentFunctions::createAndPublishObject( $publishParams );

			if( $object instanceof eZContentObject ) {
				$result['object_id']      = $object->attribute( 'id' );
				$result['object_version'] = $object->attribute( 'current_version' );
				$result['status']         = ContentSyncLogImport::STATUS_CREATED;
			} else {
				throw new Exception( 'Object creation error' );
			}

			if( count( $additionalParents ) > 0 ) {
				$additionaParentNodeIDs = array();
				foreach( $additionalParents as $additionalParent ) {
					$additionaParentNodeIDs[] = $additionalParent->attribute( 'node_id' );
				}

				eZContentOperationCollection::addAssignment(
					$object->attribute( 'main_node_id' ),
					$object->attribute( 'id' ),
					$additionaParentNodeIDs
				);
			}
		}
		return $result;
	}
}