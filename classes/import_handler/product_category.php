<?php
/**
 * @package ContentSync
 * @class   ContentSyncImportHandlerProductCategory
 * @author  Serhey Dolgushev <dolgushev.serhey@gmail.com>
 * @date    20 Jan 2014
 **/

class ContentSyncImportHandlerProductCategory extends ContentSyncImportHandlerBase
{
	public function fetchObject( $uniqueID ) {
		$node = $this->fetchNode( $uniqueID );
		return $node instanceof eZContentObjectTreeNode ? $node->attribute( 'object' ) : null;
	}

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

	public function processLocations( array $locations ) {
		$root  = eZContentObjectTreeNode::fetchByURLPath( 'buy' );
		$nodes = array();
		foreach( $locations as $location ) {
			$attrs = $location->attributes();
			if( (string) $location['type'] === ContentSyncSerializeProductCategory::$classIdentifier ) {
				$nodes[] = $this->fetchNode( (string) $location['unique_id'] );
			} elseif( (string) $location['type'] === 'root' ) {
				$nodes[] = $root;
			}
		}

		foreach( $nodes as $key => $node ) {
			if( $node instanceof eZContentObjectTreeNode === false ) {
				unset( $nodes[ $key ] );
			}
		}

		return $nodes;
	}

	public function processAttributes( array $attributes, eZContentObjectVersion $existingVerion = null ) {
		$return = $this->processSimpleAttributes( $attributes );


		// Image attriubte
		foreach( $attributes as $attribute ) {
			if( (string) $attribute['identifier'] === 'image' ) {
				$return = array_merge(
					$return,
					self::processRealtedImagesAttribute(
						$attribute,
						self::getImagesContainerNode(),
						$existingVerion
					)
				);
				break;
			}
		}

		return $return;
	}

	private function processSimpleAttributes( array $attributes ) {
		$return      = array();
		$simpleAttrs = array(
			'name',
			'short_name',
			'description',
			'category_message',
			'identifier',
			'tags',
			'xrow_prod_desc',
			'show_in_main_menu',
			'show_in_products_menu'
		);
		foreach( $attributes as $attibute ) {
			$identifier = (string) $attibute['identifier'];
			if( in_array( $identifier, $simpleAttrs ) ) {
				$return[ $identifier ] = (string) $attibute;
			}
		}
		return $return;
	}

	public static function processRealtedImagesAttribute(
		SimpleXMLElement $attribute,
		eZContentObjectTreeNode $imagesContainer,
		eZContentObjectVersion $version = null
	) {
		$return = array();
		if( $version instanceof eZContentObjectVersion ) {
			$imageHashes = self::getExistingImageHashes( $version, (string) $attribute['identifier'] );
		}

		foreach( $attribute->image as $image ) {
			if(
				isset( $image->image_file ) === false
				|| isset( $image->image_file->file_hash ) === false
				|| strlen( (string) $image->image_file->file_hash ) === 0
			) {
				// Sync request contains image without file
				continue;
			}

			$hash = (string) $image->image_file->file_hash;
			if( isset( $imageHashes[ $hash ] ) && false ) {
				$return[] = $imageHashes[ $hash ];
			} else {
				$newImage = self::publishNewImage( $imagesContainer, $image );
				if( $newImage instanceof eZContentObject ) {
					$return[] = $newImage->attribute( 'id' );
					$message = 'New image "' . $newImage->attribute( 'name' )
						. '" (Node ID: ' . $newImage->attribute( 'main_node_id' ) .  ') is created';
				} else {
					$message = 'New image publishing failed';
				}
				ContentSyncImport::addLogtMessage( $message );
			}
		}

		return implode( $return, '-' );
	}

	private static function getExistingImageHashes( eZContentObjectVersion $version, $attr ) {
		$hashes  = array();
		$dataMap = $version->attribute( 'data_map' );
		if( isset( $dataMap[ $attr ] ) === false ) {
			return $hashes;
		}

		$imageAttribute = $dataMap[ $attr ];
		switch( $imageAttribute->attribute( 'data_type_string' ) ) {
			case eZObjectRelationType::DATA_TYPE_STRING: {
				$existingImages = array( $imageAttribute->attribute( 'content' ) );
				break;
			}
			case eZObjectRelationListType::DATA_TYPE_STRING: {
				break;
			}
			default:
				return $hashes;
		}

		foreach( $existingImages as $image ) {
			if( $image instanceof eZContentObject === false ) {
				continue;
			}

			// Related object is not image
			if( $image->attribute( 'class_identifier' ) != ContentSyncSerializeImage::$classIdentifier ) {
				continue;
			}

			$dataMap      = $image->attribute( 'data_map' );
			$aliasHandler = $dataMap['image']->attribute( 'content' );

			$hash = self::getImageHash( $aliasHandler );
			if( $hash !== null ) {
				$imageHashes[ $hash ] = $image->attribute( 'id' );
			}
		}

		return $imageHashes;
	}

	public static function getImageHash( $aliasHandler ) {
		// Image has no valid file
		if( $aliasHandler->attribute( 'is_valid' ) === false ) {
			return null;
		}

		$original = $aliasHandler->attribute( 'original' );
		$file     = $original['url'];

		// Fetch image if file storage is clustered
		eZClusterFileHandler::instance( $file )->fetch();
		// Image file does not exist
		if(
			file_exists( $file ) === false
			|| (int) @filesize( $file ) === 0
		) {
			return null;
		}

		return hash_file( 'md5', $file );
	}

	public static function getImagesContainerNode() {
		return eZContentObjectTreeNode::fetchByURLPath( 'files/images/product_categories' );
	}

	public static function publishNewImage( eZContentObjectTreeNode $parentNode, SimpleXMLElement $image ) {
		$tmpFile = 'var/cache/' . (string) $image->image_file->original_filename;
		if( copy( (string) $image->image_file->file, $tmpFile ) === false ) {
			return false;
		}

		$moduleRepositories = eZModule::activeModuleRepositories( false );
		eZModule::setGlobalPathList( $moduleRepositories );

		$name    = $image->xpath( '..//attribute[@identifier="name"]' );
		$caption = $image->xpath( '..//attribute[@identifier="caption"]' );
		$params  = array(
			'parent_node_id'   => $parentNode->attribute( 'node_id' ),
			'class_identifier' => ContentSyncSerializeImage::$classIdentifier,
			'attributes'       => array(
				'name'    => (string) $name[0],
				'caption' => (string) $caption[0],
				'image'   => $tmpFile
			)
		);

		unset( $tmpFile );

		return eZContentFunctions::createAndPublishObject( $params );
	}
}