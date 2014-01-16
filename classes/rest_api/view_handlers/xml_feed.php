<?php
/**
 * @package ContentSync
 * @class   ContentSyncXMLFeedViewHandler
 * @author  Serhey Dolgushev <dolgushev.serhey@gmail.com>
 * @date    05 Jan 2014
 **/

class ContentSyncXMLFeedViewHandler implements ezcMvcViewHandler
{
	protected $zoneName;
	protected $result;
	protected $variables = array();
	private $DOM;

	public function __construct( $zoneName, $templateLocation = null ) {
		$this->zoneName = $zoneName;
	}

	public function __get( $name ) {
		return $this->variables[ $name ];
	}

	public function __isset( $name ) {
		return array_key_exists( $name, $this->variables );
	}

	public function send( $name, $value ) {
		$this->variables[ $name ] = $value;
	}

    public function process( $last ) {
    	$this->DOM = new DOMDocument( '1.0', 'utf-8' );
    	$this->DOM->formatOutput = true;

		$response = $this->DOM->createElement( $this->variables['feed']['_tag'] );
		$this->DOM->appendChild( $response );

		foreach( $this->variables['feed']['collection'] as $key => $data ) {
			if( is_array( $data ) ) {
				$this->appendArray( $key, $data, $response );
			} else {
				$response->appendChild( $this->DOM->createElement( $key, $data ) );
			}
		}

    	$this->result = $this->DOM->saveXML();
   	}

	private function appendArray( $tag, array $data, DOMNode $parentNode ) {
		if(
			is_numeric( $tag )
			&& isset( $data['_tag'] )
		) {
			$tag = $data['_tag'];
			unset( $data['_tag'] );
		}

		$node = $this->DOM->createElement( $tag );
		$parentNode->appendChild( $node );
		foreach( $data as $key => $value ) {
			if( is_scalar( $value ) ) {
				$node->appendChild( $this->DOM->createElement( $key, htmlspecialchars( $value ) ) );
			} elseif( is_array( $value ) ) {
				$this->appendArray( $key, $value, $node );
			}
		}
	}

	public function getName() {
		return $this->zoneName;
	}

	public function getResult() {
		return $this->result;
	}
}
