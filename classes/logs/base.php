<?php
/**
 * @package ContentSync
 * @class   ContentSyncLog
 * @author  Serhey Dolgushev <dolgushev.serhey@gmail.com>
 * @date    09 Jan 2014
 **/

class ContentSyncLog extends eZPersistentObject
{
	protected $cache = array();

	public function __construct( $row = array() ) {
		$this->eZPersistentObject( $row );
	}

	public function setCacheItem( $name, $val ) {
		$this->cache[ $name ] = $val;
	}

	public function getObject() {
		if( isset( $this->cache['object'] ) === false ) {
			$this->cache['object'] = eZContentObject::fetch( $this->attribute( 'object_id' ) );
		}

		return $this->cache['object'];
	}

	public function getVersion() {
		if( isset( $this->cache['version'] ) === false ) {
			$this->cache['version'] = eZContentObjectVersion::fetchVersion(
				$this->attribute( 'object_version' ),
				$this->attribute( 'object_id' )
			);
		}

		return $this->cache['version'];
	}

	public static function fetch( $id ) {
		return eZPersistentObject::fetchObject(
			static::definition(),
			null,
			array( 'id' => $id ),
			true
		);
	}

	public static function fetchList( $conditions = null, $limitations = null ) {
		return eZPersistentObject::fetchObjectList(
			static::definition(),
			null,
			$conditions,
			null,
			$limitations
		);
	}

	public static function countAll( $conds = null, $fields = null ) {
		return eZPersistentObject::count(
			static::definition(),
			$conds,
			$fields
		);
	}
}
