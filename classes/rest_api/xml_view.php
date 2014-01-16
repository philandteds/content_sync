<?php
/**
 * @package ContentSync
 * @class   ContentSyncXMLView
 * @author  Serhey Dolgushev <dolgushev.serhey@gmail.com>
 * @date    05 Jan 2014
 **/

class ContentSyncXMLView extends ezcMvcView
{
	public function __construct( ezcMvcRequest $request, ezcMvcResult $result ) {
		parent::__construct( $request, $result );

		if( isset( $result->variables['status'] ) ) {
			$result->status = new ezpRestStatusResponse( $result->variables['status'] );
		}
		$result->content = new ezcMvcResultContent( '', 'application/xml', 'UTF-8' );
	}

	public function createZones( $layout ) {
		return array( new ContentSyncXMLFeedViewHandler( 'content' ) );
	}
}
