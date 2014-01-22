<?php
/**
 * @package ContentSync
 * @class   ContentSyncImportHandlereRPBase
 * @author  Serhey Dolgushev <dolgushev.serhey@gmail.com>
 * @date    20 Jan 2014
 **/

class ContentSyncImportHandlereRPBase extends ContentSyncImportHandlerBase
{
	protected static $rootNodeURLPath       = null;
	protected static $imageContainerURLPath = null;
	protected static $simpleAttributes      = array();

	public function fetchObject( $uniqueID ) {
		$node = $this->fetchNode( $uniqueID );
		return $node instanceof eZContentObjectTreeNode ? $node->attribute( 'object' ) : null;
	}

	public function fetchNode( $uniqueID ) {
		return null;
	}

	public function processLocations( array $locations ) {
		$root = eZContentObjectTreeNode::fetchByURLPath( static::$rootNodeURLPath );
		if( $root instanceof eZContentObjectTreeNode === false ) {
			$message = 'Root node with URL path "' . static::$rootNodeURLPath . '" is missing';
			ContentSyncImport::addLogtMessage( $message );
		}

		$nodes = array();
		foreach( $locations as $location ) {
			$type     = (string) $location['type'];
			$uniqueID = (string) $location['unique_id'];

			if( $type === ContentSyncSerializeProductCategory::$classIdentifier ) {
				$node = $this->fetchNode( $uniqueID );
				if( $node instanceof eZContentObjectTreeNode ) {
					$nodes[] = $node;
				} else {
					$message = 'Parent node "' . $uniqueID . '" (type: ' . $type . ') does not exist';
					ContentSyncImport::addLogtMessage( $message );
				}
			} elseif(
				$type === 'root'
				&& $root instanceof eZContentObjectTreeNode
			) {
				$nodes[] = $root;
			}
		}

		$existingParentNodeIDs = array();
		foreach( $nodes as $key => $node ) {
			if(
				$node instanceof eZContentObjectTreeNode === false
				|| in_array( $existingParentNodeIDs, $node->attribute( 'node_id' ) )
			) {
				unset( $nodes[ $key ] );
			}

			$existingParentNodeIDs[] = $node->attribute( 'node_id' );
		}

		return array_values( $nodes );
	}

	protected function processSimpleAttributes( array $attributes ) {
		$return = array();
		foreach( $attributes as $attibute ) {
			$identifier = (string) $attibute['identifier'];
			if( in_array( $identifier, static::$simpleAttributes ) ) {
				$return[ $identifier ] = (string) $attibute;
			}
		}
		return $return;
	}

	protected static function processRealtedImagesAttribute(
		SimpleXMLElement $attribute,
		eZContentObjectTreeNode $imagesContainer,
		eZContentObjectVersion $version = null
	) {
		$return = array();
		if( $version instanceof eZContentObjectVersion ) {
			$imageHashes = static::getExistingImageHashes( $version, (string) $attribute['identifier'] );
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
			if( isset( $imageHashes[ $hash ] ) ) {
				$return[] = $imageHashes[ $hash ];
			} else {
				$newImage = static::publishNewImage( $imagesContainer, $image );
				if( $newImage instanceof eZContentObject ) {
					$return[] = $newImage->attribute( 'id' );
					$message = 'New image "' . $newImage->attribute( 'name' )
						. '" (Node ID: ' . $newImage->attribute( 'main_node_id' ) .  ') was created';
				} else {
					$message = 'New image publishing failed';
				}
				ContentSyncImport::addLogtMessage( $message );
			}
		}

		return implode( $return, '-' );
	}

	protected static function getExistingImageHashes( eZContentObjectVersion $version, $attr ) {
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

			$hash = static::getImageHash( $aliasHandler );
			if( $hash !== null ) {
				$imageHashes[ $hash ] = $image->attribute( 'id' );
			}
		}

		return $imageHashes;
	}

	protected static function getImageHash( $aliasHandler ) {
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

	protected static function getImagesContainerNode() {
		return eZContentObjectTreeNode::fetchByURLPath( static::$imageContainerURLPath );
	}

	protected static function publishNewImage( eZContentObjectTreeNode $parentNode, SimpleXMLElement $image ) {
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