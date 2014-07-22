<?php

/**
 * @package ContentSync
 * @class   ContentSyncImportHandlerXrowProduct
 * @author  Serhey Dolgushev <dolgushev.serhey@gmail.com>
 * @date    20 Jan 2014
 * */
class ContentSyncImportHandlerXrowProduct extends ContentSyncImportHandlereRPBase {

    protected static $rootNodeURLPath             = 'restricted_products';
    protected static $imageContainerURLPath       = 'files/images/products';
    protected static $removedContainerNodeURLPath = 'restricted_products/hidden_products';
    protected static $simpleAttributes            = array(
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
        'parent_category_identifiers',
        'product_complex_link'
    );
    protected $filesToRemoveAfterPublish          = array();
    protected $relatedImageHashes                 = array();

    public static function fetchNode( $uniqueID ) {
        if( strpos( $uniqueID, 'NAME#' ) !== false ) {
            return self::fetchNodeByName( $uniqueID );
        } else {
            return self::fetchNodeByProductNumberAndVersion( $uniqueID );
        }
    }

    protected static function fetchNodeByName( $uniqueID ) {
        $name        = str_replace( 'NAME#', '', $uniqueID );
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
                array( $class . '/name', '=', $name )
            )
        );

        $nodes = eZContentObjectTreeNode::subTreeByNodeID( $fetchParams, 1 );
        if( count( $nodes ) > 0 ) {
            return $nodes[0];
        }

        return null;
    }

    protected static function fetchNodeByProductNumberAndVersion( $uniqueID ) {
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

    public function processAttributes( array $attributes, $uniqueID, eZContentObjectVersion $existingVerion = null ) {
        $return = $this->processSimpleAttributes( $attributes );
        $object = $this->fetchObject( $uniqueID );

        foreach( $attributes as $attribute ) {
            $identifier = (string) $attribute['identifier'];

            // Logo
            if( $identifier === 'xrow_logo' ) {
                $imageLocalFile = self::processImageAttribute( $attribute, $existingVerion );
                if( $imageLocalFile !== null ) {
                    $this->filesToRemoveAfterPublish[] = $imageLocalFile;
                    $return[$identifier]               = $imageLocalFile;
                }
                continue;
            }

            // Shop homepage image and Images
            if(
                $identifier === 'image' || $identifier === 'images'
            ) {
                $return[$identifier] = $this->processRealtedImagesAttributeCustom(
                    $attribute, self::getImagesContainerNode(), $object
                );
                continue;
            }

            // Required related products and Optional related products
            if(
                $identifier === 'required_related_products' || $identifier === 'optional_related_products'
            ) {
                $return[$identifier] = self::processRelatedProducts( $attribute );
                continue;
            }

            // Colour Image map
            // This attribute is handled in import method, becase it has no fromString implementation
            if( $identifier === 'colour_image_map' ) {
                $return['colour_image_map_info'] = self::processColourImageMap( $attribute );
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

        if( $result['status'] !== ContentSyncLogImport::STATUS_SKIPPED ) {
            foreach( $objectData['attributes']['colour_image_map_info'] as $colourImageMap ) {
                self::insertOrUpdateColourImageMap(
                    $colourImageMap, $objectData['attributes']['product_id'], $objectData['attributes']['version']
                );
            }
        }

        return $result;
    }

    public static function insertOrUpdateColourImageMap( array $data, $itemNumber, $versionNumber ) {
        $db            = eZDB::instance();
        $itemNumber    = $db->escapeString( $itemNumber );
        $versionNumber = $db->escapeString( $versionNumber );
        $colour        = $db->escapeString( $data['colour'] );

        $query  = '
			SELECT Imageid FROM product_image
			WHERE
				ItemNumber = "' . $itemNumber . '"
				AND Version = "' . $versionNumber . '"
				AND Colour = "' . $colour . '"
		';
        $result = $db->arrayQuery( $query );
        if( count( $result ) === 0 ) {
            $query = '
				INSERT INTO product_image (ItemNumber, Imageid, Version, Colour, CSSColour)
				VALUES (
					"' . $itemNumber . '",
					"' . implode( ',', $data['image_ids'] ) . '",
					"' . $versionNumber . '",
					"' . $colour . '",
					"' . $db->escapeString( $data['css_colour'] ) . '"
				)
			';
        } else {
            //$imageIDs = array_merge( $data['image_ids'], explode( ',', $result[0]['Imageid'] ) );
            $imageIDs = $data['image_ids'];
            $query    = '
				UPDATE product_image
				SET Imageid = "' . implode( ',', array_unique( $imageIDs ) ) . '"
				WHERE
					ItemNumber = "' . $itemNumber . '"
					AND Version = "' . $versionNumber . '"
					AND Colour = "' . $colour . '"
			';
        }
        $db->query( $query );
    }

    protected function processRelatedProducts( SimpleXMLElement $attribute ) {
        $relatedProductIDs = array();

        foreach( $attribute->product as $product ) {
            $uniqueID = (string) $product['unique_id'];
            $object   = self::fetchObject( $uniqueID );
            if( $object instanceof eZContentObject === false ) {
                $message = 'Can not fetch related product'
                    . ' (' . (string) $attribute['identifier'] . ')'
                    . ' (uniqueID: "' . $uniqueID . '")';
                ContentSyncImport::addLogtMessage( $message );
                continue;
            }

            $relatedProductIDs[] = $object->attribute( 'id' );
        }

        return implode( '-', $relatedProductIDs );
    }

    protected function processRealtedImagesAttributeCustom(
    SimpleXMLElement $attribute, eZContentObjectTreeNode $imagesContainer, eZContentObject $object = null
    ) {
        $return      = array();
        $imageHashes = array();
        if( $object instanceof eZContentObject ) {
            $imageHashes = static::getExistingImageHashes( $object, (string) $attribute['identifier'] );
        }

        foreach( $attribute->image as $image ) {
            if(
                isset( $image->image_file ) === false || isset( $image->image_file->file_hash ) === false || strlen( (string) $image->image_file->file_hash )
                === 0
            ) {
                // Sync request contains image without file
                continue;
            }

            $hash = (string) $image->image_file->file_hash;
            if( isset( $imageHashes[$hash] ) ) {
                $imageID                         = $imageHashes[$hash];
                $return[]                        = $imageID;
                $this->relatedImageHashes[$hash] = (int) $imageID;
            } else {
                $newImage = static::publishNewImage( $imagesContainer, $image );
                if( $newImage instanceof eZContentObject ) {
                    $imageID                         = (int) $newImage->attribute( 'id' );
                    $return[]                        = $imageID;
                    $this->relatedImageHashes[$hash] = $imageID;

                    $message = 'New image "' . $newImage->attribute( 'name' )
                        . '" (' . (string) $attribute['identifier'] . ')'
                        . ' (Node ID: ' . $newImage->attribute( 'main_node_id' ) . ') was created';
                } else {
                    $message = 'New image publishing failed';
                }
                ContentSyncImport::addLogtMessage( $message );
            }
        }

        return implode( $return, '-' );
    }

    protected function processColourImageMap( SimpleXMLElement $attribute ) {
        $return = array();
        foreach( $attribute->sku_image as $sku_image ) {
            $colour    = (string) $sku_image['colour'];
            $CSSColour = (string) $sku_image['css_colour'];
            $imageIDs  = array();

            foreach( $sku_image->image as $image ) {
                if(
                    isset( $image->image_file ) === false || isset( $image->image_file->file_hash ) === false || strlen( (string) $image->image_file->file_hash )
                    === 0
                ) {
                    // Sync request contains image without file
                    continue;
                }

                $hash = (string) $image->image_file->file_hash;
                if( isset( $this->relatedImageHashes[$hash] ) === false ) {
                    continue;
                }

                $imageIDs[] = $this->relatedImageHashes[$hash];
            }

            $return[] = array(
                'colour'     => $colour,
                'css_colour' => $CSSColour,
                'image_ids'  => $imageIDs
            );
        }

        return $return;
    }

    public function remove( array $objectData ) {
        $result = array(
            'object_id'      => null,
            'object_version' => null,
            'status'         => ContentSyncLogImport::STATUS_REMOVED
        );
        $object = $this->fetchObject( $objectData['unique_id'] );

        if( $object instanceof eZContentObject === false ) {
            $message          = 'Unable to fetch object by "' . $objectData['unique_id'] . '" unique ID';
            ContentSyncImport::addLogtMessage( $message );
            $result['status'] = ContentSyncLogImport::STATUS_SKIPPED;
            return $result;
        }
        $result['object_id']      = $object->attribute( 'id' );
        $result['object_version'] = $object->attribute( 'current_version' );

        $removedContainer = eZContentObjectTreeNode::fetchByURLPath( static::$removedContainerNodeURLPath );
        if( $removedContainer instanceof eZContentObjectTreeNode === false ) {
            $message          = 'Node with URL path "' . static::$removedContainerNodeURLPath . '" is missing';
            ContentSyncImport::addLogtMessage( $message );
            $result['status'] = ContentSyncLogImport::STATUS_SKIPPED;
            return $result;
        }

        $removeNodes = array();
        $nodes       = $object->attribute( 'assigned_nodes' );
        foreach( $nodes as $node ) {
            if( $node->attribute( 'is_main' ) === false ) {
                $removeNodes[] = $node->attribute( 'node_id' );
            }
        }

        if( count( $removeNodes ) > 0 ) {
            $message = 'Removing following locations: ' . implode( ', ', self::getNodeURLPathes( $removeNodes ) );
            ContentSyncImport::addLogtMessage( $message );
            eZContentOperationCollection::removeNodes( $removeNodes );
        }

        $message = 'Moving main node under "' . $removedContainer->attribute( 'path_identification_string' ) . '"';
        ContentSyncImport::addLogtMessage( $message );
        eZContentOperationCollection::moveNode(
            $object->attribute( 'main_node_id' ), $object->attribute( 'id' ), $removedContainer->attribute( 'node_id' )
        );

        return $result;
    }

}
