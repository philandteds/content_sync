<?php

/**
 * @package ContentSync
 * @class   ContentSyncImport
 * @author  Serhey Dolgushev <dolgushev.serhey@gmail.com>
 * @date    23 Jan 2014
 * */
class ContentSyncContentFunctions extends eZContentFunctions {

	static function createAndPublishObject($params) {
		$parentNodeID = $params['parent_node_id'];
		$classIdentifier = $params['class_identifier'];
		$languageCode = isset($params['language']) ? $params['language'] : false;
		$creatorID = isset($params['creator_id']) ? $params['creator_id'] : false;
		$attributesData = isset($params['attributes']) ? $params['attributes'] : false;
		$storageDir = isset($params['storage_dir']) ? $params['storage_dir'] : '';

		$contentObject = false;

		$parentNode = eZContentObjectTreeNode::fetch($parentNodeID, false, false);

		if (is_array($parentNode)) {
			$contentClass = eZContentClass::fetchByIdentifier($classIdentifier);
			if ($contentClass instanceof eZContentClass) {
				$db = eZDB::instance();
				$db->begin();

				$contentObject = $contentClass->instantiate($creatorID, 0, false, $languageCode);

				if (array_key_exists('remote_id', $params))
					$contentObject->setAttribute('remote_id', $params['remote_id']);

				if (array_key_exists('section_id', $params))
					$contentObject->setAttribute('section_id', $params['section_id']);

				$contentObject->store();

				$nodeAssignment = eZNodeAssignment::create(array('contentobject_id' => $contentObject->attribute('id'),
							'contentobject_version' => $contentObject->attribute('current_version'),
							'parent_node' => $parentNodeID,
							'is_main' => 1,
							'sort_field' => $contentClass->attribute('sort_field'),
							'sort_order' => $contentClass->attribute('sort_order')));
				$nodeAssignment->store();

				$version = $contentObject->version(1);
				$version->setAttribute('modified', eZDateTime::currentTimeStamp());
				$version->setAttribute('status', eZContentObjectVersion::STATUS_DRAFT);
				$version->store();

				if (is_array($attributesData) && !empty($attributesData)) {
					$attributes = $contentObject->contentObjectAttributes(true, false, $languageCode);

					foreach ($attributes as $attribute) {
						$attributeIdentifier = $attribute->attribute('contentclass_attribute_identifier');
						if (isset($attributesData[$attributeIdentifier])) {
							$dataString = $attributesData[$attributeIdentifier];
							switch ($datatypeString = $attribute->attribute('data_type_string')) {
								case 'ezimage':
								case 'ezbinaryfile':
								case 'ezmedia': {
										$dataString = $storageDir . $dataString;
										break;
									}
								default:
							}

							$attribute->fromString($dataString);
							$attribute->store();
						}
					}
				}

				$db->commit();

				$operationResult = eZOperationHandler::execute('content', 'publish', array('object_id' => $contentObject->attribute('id'),
							'version' => 1));
			} else {
				eZDebug::writeError("Content class with identifier '$classIdentifier' doesn't exist.", __METHOD__);
			}
		} else {
			eZDebug::writeError("Node with id '$parentNodeID' doesn't exist.", __METHOD__);
		}

		return $contentObject;
	}

}
