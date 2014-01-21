<?php
/**
 * @package ContentSync
 * @class   ContentSyncImportHandlerXrowProduct
 * @author  Serhey Dolgushev <dolgushev.serhey@gmail.com>
 * @date    20 Jan 2014
 **/

class ContentSyncImportHandlerXrowProduct extends ContentSyncImportHandlerBase
{
	public function processAttributes( array $attributes, eZContentObjectVersion $existingVerion = null ) {
		return array();
	}

	public function processLocations( array $locations ) {
		return array();
	}
}