<?php
/**
 * @package ContentSync
 * @class   ContentSyncImportHandlerBase
 * @author  Serhey Dolgushev <dolgushev.serhey@gmail.com>
 * @date    20 Jan 2014
 **/

abstract class ContentSyncImportHandlerBase
{
	protected static $instances	= array();

	/**
	 * Returns import handler
	 * @param string $classIdentifier
	 * @return ContentSyncImportHandlerBase|null
	 */
	public final static function get( $classIdentifier ) {
		$className = self::getClassName( $classIdentifier );
		if( class_exists( $className ) === false ) {
			return null;
		}

		$reflector = new ReflectionClass( $className );
		if( $reflector->isSubclassOf( __CLASS__ ) === false ) {
			return null;
		}

		return call_user_func( array( $className, 'getInstance' ) );
	}

	/**
	 * Returns import handler class name
	 * @param string $classIdentifier
	 * @return string
	 */
	public static function getClassName( $classIdentifier ) {
		return 'ContentSyncImportHandler' . self::toCamelCase( $classIdentifier );
	}

	/**
	 * Transforms the string to camel case
	 * @param string $str
	 * @return string
	 */
	protected final static function toCamelCase( $str ) {
		return str_replace( ' ', '', ucwords( str_replace( '_', ' ', $str ) ) );
	}

	/**
	 * Single instance of called class
	 * @return static
	 */
	public final static function getInstance() {
		$class = get_called_class();
		if( isset( self::$instances[ $class ] ) === false ) {
			self::$instances[ $class ] = new $class;
		}

		return self::$instances[ $class ];
	}

	/**
	 * @param array[SimpleXMLElement] $attributes
	 * @param eZContentObjectVersion $existingVerion
	 * @return array
	 */
	public function processAttributes( array $attributes, eZContentObjectVersion $existingVerion = null ) {
		return array();
	}

	/**
	 * @param array[SimpleXMLElement] $locations
	 * @param array $objectData
	 * @return array[eZContentObjectTreeNode]
	 */
	public function processLocations( array $locations, array $objectData ) {
		return array();
	}

	/**
	 * Fetches content object by unique ID
	 * @param string $uniqueID
	 * @return eZContentObject|null
	 */
	public function fetchObject( $uniqueID ) {
		return null;
	}

	/**
	 * Creates or updates content object
	 * @param array $objectData
	 * @param eZContentObjectVersion $existingVersion
	 * @return array
	 */
	public function import( array $objectData, eZContentObjectVersion $existingVersion = null ) {
		return array(
			'object_id'      => null,
			'object_version' => null,
			'status'         => ContentSyncLogImport::STATUS_SKIPPED
		);
	}

	/**
	 * Removes content object
	 * @param array $objectData
	 * @return array
	 */
	public function remove( array $objectData ) {
		return array(
			'object_id'      => null,
			'object_version' => null,
			'status'         => ContentSyncLogImport::STATUS_REMOVED
		);
	}
}