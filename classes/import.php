<?php

/**
 * @package ContentSync
 * @class   ContentSyncImport
 * @author  Serhey Dolgushev <dolgushev.serhey@gmail.com>
 * @date    20 Jan 2014
 * */
class ContentSyncImport {

    protected static $instance    = null;
    protected static $logMessages = array();
    protected $handler            = null;
    protected $DOMDocument        = null;
    protected $objectData         = array(
        'unique_id'           => null,
        'language'            => null,
        'type'                => null,
        'is_main_translation' => null,
        'attributes'          => array(),
        'locations'           => array()
    );

    /**
     * Recieves the content object and returns it`serializer
     * @param eZContentObject $object
     * @return ContentSyncSerializeBase|null
     */
    public final static function get( eZContentObject $object ) {
        $className = 'ContentSyncUnserialize' . self::toCamelCase( $object->attribute( 'class_identifier' ) );
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
     * @return ContentSyncImport $instance
     */
    public final static function getInstance() {
        $class = get_called_class();
        if( self::$instance === null ) {
            self::$instance = new $class;
        }

        self::$logMessages = array();

        return self::$instance;
    }

    /**
     * Processes XML object data and stores data in objectData variable
     * @param string $xml
     * @return array
     */
    public function process( $xml ) {
        $this->DOMDocument = new DOMDocument();
        if( $this->DOMDocument->loadXML( $xml ) === false ) {
            throw new Exception( 'Invalid object data XML' );
        }

        ContentSyncType::disable();

        $moduleRepositories = eZModule::activeModuleRepositories( false );
        eZModule::setGlobalPathList( $moduleRepositories );

        $object = $this->DOMDocument->getElementsByTagName( 'object' );
        if( $object->length === 0 ) {
            throw new Exception( 'Missing "object" element' );
        }

        $this->processObjectData();
        $this->validateObjectData();
        $this->fetchImportHandler();

        if( $object->item( 0 )->hasAttribute( 'remove' ) ) {
            return $this->remove();
        } else {
            $this->processAttributes();
            $this->processLocations();
            return $this->import();
        }
    }

    private function processObjectData() {
        $object = $this->DOMDocument->getElementsByTagName( 'object' )->item( 0 );
        foreach( array_keys( $this->objectData ) as $attr ) {
            // skip attributes and locations
            if( is_array( $this->objectData[$attr] ) === true ) {
                continue;
            }

            if( $object->hasAttribute( $attr ) === false ) {
                throw new Exception( 'Missing "' . $attr . '" attribute' );
            }
            if( strlen( $object->getAttribute( $attr ) ) == 0 ) {
                throw new Exception( '"' . $attr . '" attribute can not be empty' );
            }

            $this->objectData[$attr] = $object->getAttribute( $attr );
        }

        $this->objectData['is_main_translation'] = (bool) $this->objectData['is_main_translation'];
    }

    private function validateObjectData() {
        if( strlen( $this->objectData['unique_id'] ) === 0 ) {
            throw new Exception( 'Empty "unique_id"' );
        }

        $contentClass = eZContentClass::fetchByIdentifier( $this->objectData['type'] );
        if( $contentClass instanceof eZContentClass === false ) {
            throw new Exception( 'Content class "' . $this->objectData['type'] . '" does not exist' );
        }

        $language = eZContentLanguage::fetchByLocale( $this->objectData['language'] );
        if( $language instanceof eZContentLanguage === false ) {
            throw new Exception( 'Language "' . $this->objectData['language'] . '" does not exist' );
        }
    }

    private function fetchImportHandler() {
        $this->handler = ContentSyncImportHandlerBase::get( $this->objectData['type'] );
        if( $this->handler instanceof ContentSyncImportHandlerBase === false ) {
            throw new Exception( 'Unable to get import handler for "' . $this->objectData['type'] . '" content class' );
        }
    }

    private function processAttributes() {
        $xml        = simplexml_load_string( $this->DOMDocument->saveXML(), null, LIBXML_NOCDATA );
        $attributes = $xml->xpath( '/object/attributes/attribute' );

        $existingVerion                 = $this->getExisitingObjectVersion();
        $this->objectData['attributes'] = $this->handler->processAttributes( $attributes, $this->objectData['unique_id'], $existingVerion );

        unset( $xml );
    }

    private function processLocations() {
        $xml       = simplexml_import_dom( $this->DOMDocument );
        $locations = $xml->xpath( '/object/locations/location' );

        $this->objectData['locations'] = $this->handler->processLocations( $locations, $this->objectData );
        if( count( $this->objectData['locations'] ) === 0 ) {
            throw new Exception( 'Unable to fetch at least one parent node' );
        }

        unset( $xml );
    }

    private function getExisitingObjectVersion() {
        $version = null;
        $object  = $this->handler->fetchObject( $this->objectData['unique_id'] );
        if( $object instanceof eZContentObject === false ) {
            return $version;
        }

        return self::getLatestTranslatedVersion( $object->attribute( 'id' ), $this->objectData['language'] );
    }

    public static function getLatestTranslatedVersion( $objectID, $locale ) {
        $language = eZContentLanguage::fetchByLocale( $locale );
        $versions = eZPersistentObject::fetchObjectList(
                eZContentObjectVersion::definition(), null, array(
                'initial_language_id' => $language->attribute( 'id' ),
                'contentobject_id'    => $objectID
                ), array(
                'version' => 'desc'
                )
        );
        if( count( $versions ) > 0 ) {
            return $versions[0];
        }

        return null;
    }

    private function import() {
        $existingVersion = $this->getExisitingObjectVersion();
        return $this->handler->import( $this->objectData, $existingVersion );
    }

    private function remove() {
        return $this->handler->remove( $this->objectData );
    }

    public static function addLogtMessage( $message ) {
        self::$logMessages[] = $message;
    }

    public static function getResultMessage() {
        $message = implode( "\n", self::$logMessages );
        self::$logMessages = array();
        return $message;
    }

}
