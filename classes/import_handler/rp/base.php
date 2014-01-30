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
		$node = static::fetchNode( $uniqueID );
		return $node instanceof eZContentObjectTreeNode ? $node->attribute( 'object' ) : null;
	}

	public static function fetchNode( $uniqueID ) {
		return null;
	}

	public function processLocations( array $locations, array $objectData ) {
		$root = eZContentObjectTreeNode::fetchByURLPath( static::$rootNodeURLPath );
		if( $root instanceof eZContentObjectTreeNode === false ) {
			$message = 'Root node with URL path "' . static::$rootNodeURLPath . '" is missing';
			ContentSyncImport::addLogtMessage( $message );
		}

		$nodes = array();
		foreach( $locations as $location ) {
			$type     = (string) $location['type'];
			$uniqueID = (string) $location['unique_id'];

			if( $uniqueID == $objectData['unique_id'] ) {
				$message = 'Node can not be located under itself. Skipping parent node "' . $uniqueID . '" (type: ' . $type . ')';
				ContentSyncImport::addLogtMessage( $message );
				continue;
			}

			if( $type == 'root' ) {
				if( $root instanceof eZContentObjectTreeNode ) {
					$nodes[] = $root;
				}
			} else {
				$className = self::getClassName( $type );
				if( is_callable( array( $className, 'fetchNode' ) ) === false ) {
					$message = $className . ':: is not callable. Skipping parent node "' . $uniqueID . '" (type: ' . $type . ')';
					ContentSyncImport::addLogtMessage( $message );
					continue;
				}

				if( strlen( $uniqueID ) === 0 ) {
					$message = 'Skipping node with empty unique ID';
					ContentSyncImport::addLogtMessage( $message );
					continue;
				}

				$node = $className::fetchNode( $uniqueID );
				if( $node instanceof eZContentObjectTreeNode === false ) {
					$message = 'Parent node "' . $uniqueID . '" (type: ' . $type . ') does not exist';
					ContentSyncImport::addLogtMessage( $message );
					continue;
				}
				$nodes[] = $node;
			}
		}

		return $this->uniqueNodes( $nodes );
	}

	protected function uniqueNodes( array $nodes ) {
		$nodeIDs = array();
		foreach( $nodes as $key => $node ) {
			if(
				$node instanceof eZContentObjectTreeNode === false
				|| in_array( $node->attribute( 'node_id' ), $nodeIDs )
			) {
				unset( $nodes[ $key ] );
			}

			$nodeIDs[] = $node->attribute( 'node_id' );
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

	protected static function processImageAttribute(
		SimpleXMLElement $attribute,
		eZContentObjectVersion $version = null
	) {
		if(
			isset( $attribute->image_file ) === false
			|| isset( $attribute->image_file->file_hash ) === false
			|| strlen( (string) $attribute->image_file->file_hash ) === 0
		) {
			// There is invalid image in the import request
			return null;
		}

		$identifier   = (string) $attribute['identifier'];
		$existingHash = null;

		if( $version instanceof eZContentObjectVersion ) {
			$dataMap = $version->attribute( 'data_map' );
			if( isset( $dataMap[ $identifier ] ) ) {
				$aliasHandler = $dataMap[ $identifier ]->attribute( 'content' );
				$existingHash = static::getImageHash( $aliasHandler );
			}
		}

		// No image change is required
		if( $existingHash == (string) $attribute->image_file->file_hash ) {
			return null;
		}

		// Copy image to local file, it should be removed after object being published
		$tmpFile = 'var/cache/' . (string) $attribute->image_file->original_filename;
		if( copy( (string) $attribute->image_file->file, $tmpFile ) === false ) {
			return null;
		}

		return $tmpFile;
	}

	protected static function processRealtedImagesAttribute(
		SimpleXMLElement $attribute,
		eZContentObjectTreeNode $imagesContainer,
		eZContentObject $object = null
	) {
		$return      = array();
		$imageHashes = array();
		if( $object instanceof eZContentObject ) {
			$imageHashes = static::getExistingImageHashes( $object, (string) $attribute['identifier'] );
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
						. '" (' . (string) $attribute['identifier'] . ')'
						. ' (Node ID: ' . $newImage->attribute( 'main_node_id' ) .  ') was created';
				} else {
					$message = 'New image publishing failed';
				}
				ContentSyncImport::addLogtMessage( $message );
			}
		}

		return implode( $return, '-' );
	}

	protected static function getExistingImageHashes( eZContentObject $object, $attr ) {
		$hashes  = array();
		$dataMap = $object->attribute( 'data_map' );
		if( isset( $dataMap[ $attr ] ) === false ) {
			return $hashes;
		}

		$currentVersion = $object->attribute( 'current' );
		if( $currentVersion instanceof eZContentObjectVersion === false ) {
			return $hashes;
		}

		$existingImages = array();
		$languages      = $currentVersion->translations( false );
		foreach( $languages as $language ) {
			$currentVersion->resetDataMap();
			$currentVersion->CurrentLanguage = $language;
			$dataMap = $currentVersion->attribute( 'data_map' );

			$imageAttribute = $dataMap[ $attr ];
			switch( $imageAttribute->attribute( 'data_type_string' ) ) {
				case eZObjectRelationType::DATA_TYPE_STRING: {
					$existingImages[] = $imageAttribute->attribute( 'content' );
					break;
				}
				case eZObjectRelationListType::DATA_TYPE_STRING: {
					$relations      = $imageAttribute->attribute( 'content' );
					$relations      = $relations['relation_list'];
					foreach( $relations as $relation ) {
						$image = eZContentObject::fetch( $relation['contentobject_id'] );
						if( $image instanceof eZContentObject === false ) {
							continue;
						}

						$existingImages[] = $image;
					}

					break;
				}
				default:
					return $hashes;
			}
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
				$hashes[ $hash ] = $image->attribute( 'id' );
			}

		}

		return $hashes;
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

		$name    = $image->xpath( './/attribute[@identifier="name"]' );
		$caption = $image->xpath( './/attribute[@identifier="caption"]' );
		$params  = array(
			'parent_node_id'   => $parentNode->attribute( 'node_id' ),
			'class_identifier' => ContentSyncSerializeImage::$classIdentifier,
			'attributes'       => array(
				'name'    => (string) $name[0],
				'caption' => (string) $caption[0],
				'image'   => $tmpFile
			)
		);

		$result = eZContentFunctions::createAndPublishObject( $params );
		@unlink( $tmpFile );

		return $result;
	}

	public function import( array $objectData, eZContentObjectVersion $existingVersion = null ) {
		$object = $this->fetchObject( $objectData['unique_id'] );
		if( $object instanceof eZContentObject === false ) {
			return $this->createObject( $objectData );
		} else {
			return $this->updateObject( $objectData, $object, $existingVersion );
		}
		return $result;
	}

	protected function createObject( array $objectData ) {
		$result = array(
			'object_id'      => null,
			'object_version' => null,
			'status'         => ContentSyncLogImport::STATUS_SKIPPED
		);

		$mainParent        = $objectData['locations'][0];
		$additionalParents = array_slice( $objectData['locations'], 1 );

		$publishParams = array(
			'class_identifier' => $objectData['type'],
			'attributes'       => $objectData['attributes'],
			'language'         => $objectData['language'],
			'parent_node_id'   => $mainParent->attribute( 'node_id' )
		);

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

			$message = 'Creating additional locations under following nodes: ' . implode( ', ', self::getNodeURLPathes( $additionaParentNodeIDs ) );
			ContentSyncImport::addLogtMessage( $message );
			eZContentOperationCollection::addAssignment(
				$object->attribute( 'main_node_id' ),
				$object->attribute( 'id' ),
				$additionaParentNodeIDs
			);
		}

		return $result;
	}

	protected function updateObject(
		array $objectData,
		eZContentObject $object
	) {
		$result = array(
			'object_id'      => null,
			'object_version' => null,
			'status'         => ContentSyncLogImport::STATUS_SKIPPED
		);

		$publishParams = array(
			'attributes' => $objectData['attributes'],
			'language'   => $objectData['language']
		);

		$newVersion = ContentSyncContentFunctions::updateAndPublishObject( $object, $publishParams );
		if( $newVersion instanceof eZContentObjectVersion ) {
			$result['object_id']      = $object->attribute( 'id' );
			$result['object_version'] = $newVersion->attribute( 'version' );
			$result['status']         = ContentSyncLogImport::STATUS_UPDATED;
		} else {
			throw new Exception( 'Object update error' );
		}


		$newParentNodeIDs = array();
		foreach( $objectData['locations'] as $node ) {
			$newParentNodeIDs[] = $node->attribute( 'node_id' );
		}
		$existingParentNodeIDs = array();
		$existingNodes         = $object->attribute( 'assigned_nodes' );
		foreach( $existingNodes as $existingNode ) {
			$existingParentNodeIDs[ $existingNode->attribute( 'node_id' ) ] = $existingNode->attribute( 'parent_node_id' );
		}

		$addLocations = array();
		foreach( $newParentNodeIDs as $newParentNodeID ) {
			if( in_array( $newParentNodeID, $existingParentNodeIDs ) === false ) {
				$addLocations[] = $newParentNodeID;
			}
		}
		if( count( $addLocations ) > 0 ) {
			$message = 'Creating new locations under following nodes: ' . implode( ', ', self::getNodeURLPathes( $addLocations ) );
			ContentSyncImport::addLogtMessage( $message );
			eZContentOperationCollection::addAssignment(
				$object->attribute( 'main_node_id' ),
				$object->attribute( 'id' ),
				$addLocations
			);
		}

		$removeLocations = array();
		foreach( $existingParentNodeIDs as $nodeID => $existingParentNodeID ) {
			if( in_array( $existingParentNodeID, $newParentNodeIDs ) === false ) {
				$removeLocations[] = $nodeID;
			}
		}
		if( count( $removeLocations ) > 0 ) {
			$message = 'Removing following locations: ' . implode( ', ', self::getNodeURLPathes( $removeLocations ) );
			ContentSyncImport::addLogtMessage( $message );
			eZContentOperationCollection::removeNodes( $removeLocations );
		}

		return $result;
	}

	protected static function getNodeURLPathes( array $nodeIDs ) {
		$return = array();
		foreach( $nodeIDs as $nodeID ) {
			$node = eZContentObjectTrashNode::fetch( $nodeID, false, false );
			if( is_array( $node ) ) {
				$return[] = $node['path_identification_string'];
			}
		}
		return $return;
	}
}