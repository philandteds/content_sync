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
		$className = 'ContentSyncImportHandler' . self::toCamelCase( $classIdentifier );
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
	 * @param array[SimpleXMLElement]
	 * @return array
	 */
	public function processAttributes( array $attributes ) {
		return array();
	}

	/**
	 * @param array[SimpleXMLElement]
	 * @return array[eZContentObjectTreeNode]
	 */
	public function processLocations( array $locations ) {
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
}