<?php
/**
 * @package ContentSync
 * @class   ContentSyncSerializeBase
 * @author  Serhey Dolgushev <dolgushev.serhey@gmail.com>
 * @date    17 Jan 2014
 **/

abstract class ContentSyncSerializeBase
{
	protected static $instances	 = array();
	public static $classIdentifier = null;

	/**
	 * Recieves the content object and returns it`serializer
	 * @param eZContentObject $object
	 * @return ContentSyncSerializeBase|null
	 */
	public final static function get( eZContentObject $object ) {
		$className = 'ContentSyncSerialize' . self::toCamelCase( $object->attribute( 'class_identifier' ) );
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
	 * Recieves the content object, and returns the data for objects which should by synced
	 * @param eZContentObject $object
	 * @param int $versionNumber
	 * @return array The list of content objects with version
	 */
	public function getObjectsToSync( eZContentObject $object, $versionNumber = null ) {
		$version = $versionNumber === null ? $object->attribute( 'current' ) : $object->version( $versionNumber );
		return array(
			array(
				'object'  => $object,
				'version' => $version
			)
		);
	}

	/**
	 * Returns object data XML, which will be sent as content sync request
	 * @param eZContentObject $object
	 * @param eZContentObjectVersion $version
	 * @return string Object data XML
	 */
	public function getObjectData( eZContentObject $object, eZContentObjectVersion $version ) {
		return '<request></request>';
	}
}