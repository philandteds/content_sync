<?php
/**
 * @package ContentSync
 * @class   ContentSyncViewController
 * @author  Serhey Dolgushev <dolgushev.serhey@gmail.com>
 * @date    05 Jan 2014
 **/

class ContentSyncViewController implements ezpRestViewControllerInterface
{
	public function loadView( ezcMvcRoutingInformation $routeInfo, ezcMvcRequest $request, ezcMvcResult $result ) {
		return new ContentSyncXMLView( $request, $result );
	}
}
