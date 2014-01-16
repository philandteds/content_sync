<?php
/**
 * @package ContentSync
 * @class   ContentSyncProvider
 * @author  Serhey Dolgushev <dolgushev.serhey@gmail.com>
 * @date    05 Jan 2014
 **/

class ContentSyncProvider implements ezpRestProviderInterface
{
	public function getRoutes()	{
		return array(
			'start' => new ezpMvcRegexpRoute(
				'@^/content_sync/start$@',
				'ContentSyncController',
				array(
					'http-post' => 'start',
					'http-get'  => 'start'
				)
			)
		);
	}

	public function getViewController() {
		return new ContentSyncViewController();
	}
}
