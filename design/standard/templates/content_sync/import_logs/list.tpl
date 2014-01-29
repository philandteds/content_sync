<form method="post" action="{'content_sync/import_logs'|ezurl( 'no' )}">
	<div class="box-content">
		<div class="context-attributes">
			<div class="object">
				<div class="block">
    				<label>{'Filter object'|i18n( 'extension/content_sync' )}:</label>
					<p>
					{if $filter_object}
						<a href="{$filter_object.main_node.url_alias|ezurl( 'no' )}">{$filter_object.name|wash}</a>
					{else}
						{'none'|i18n( 'extension/content_sync' )}
					{/if}
					</p>
					<input class="button" type="submit" name="BrowseFilterObjectButton" value="{'Select object'|i18n( 'extension/content_sync' )}" />
				</div>
			</div>
		</div>
	</div>
</form>
		
<div class="context-block">

	<div class="box-header"><div class="box-tc"><div class="box-ml"><div class="box-mr"><div class="box-tl"><div class="box-tr">
		<h1 class="context-title">&nbsp;{'Import logs'|i18n( 'extension/content_sync' )} ({$total_count})</h1>
		<div class="header-subline"></div>
	</div></div></div></div></div></div>

	<div class="box-ml"><div class="box-mr"><div class="box-content">
		{if $logs|count|gt( 0 )}
			<table class="list" cellspacing="0" cellpadding="0">
				<thead>
					<tr>
						<th>{'User'|i18n( 'extension/content_sync' )}</th>
						<th>{'Object'|i18n( 'extension/content_sync' )}</th>
						<th>{'Version'|i18n( 'extension/content_sync' )}</th>
						<th>{'Date'|i18n( 'extension/content_sync' )}</th>
						<th>{'Status'|i18n( 'extension/content_sync' )}</th>
						<th>{'Import time'|i18n( 'extension/content_sync' )}</th>
						<th class="tight">&nbsp;</th>
					</tr>
				</thead>
				<tbody>
					{foreach $logs as $log sequence array( 'bgdark', 'bglight' ) as $style }
					<tr class="{$style}">
						<td>{if $log.user}<a href="{$log.user.main_node.url_alias|ezurl( 'no' )}" target="_blank">{$log.user.name|wash}</a>{else}{'doesn`t exist'|i18n( 'extension/content_sync' )}{/if}</td>
						<td>{if $log.object}<a href="{$log.object.main_node.url_alias|ezurl( 'no' )}" target="_blank">{if $log.object.name}{$log.object.name|wash}{else}{'empty name'|i18n( 'extension/content_sync' )}{/if}</a>{else}{'doesn`t exist'|i18n( 'extension/content_sync' )}{/if}</td>
						<td>{if $log.version}<a href="{concat( '/content/versionview/', $log.object_id, '/', $log.object_version, '/', $log.version.initial_language.locale )|ezurl( 'no' )}" target="_blank">{$log.object_version} ({$log.version.initial_language.locale})</a>{else}{$log.object_version}{/if}</td>
						<td>{$log.date|datetime( 'custom', '%d.%m.%Y %H:%i:%s' )}</td>
						<td>{$log.status_description}</td>
						<td>{$log.import_time}</td>
						<td class="tight"><a href="{concat( 'content_sync/import_details'|ezurl( 'no' ), '/', $log.id )}"><img src="{'share/icons/crystal-admin/16x16_original/mimetypes/readme.png'|ezroot( 'no' )}" alt="{'Details'|i18n( 'extension/ontent_sync' )}" /></a></td>
					</tr>
					{/foreach}
				</tbody>
			</table>
		{/if}
	</div></div></div>

	{include
		uri='design:navigator/google.tpl'
		page_uri='content_sync/import_logs'
		item_count=$total_count
		view_parameters=hash( 'limit', $limit, 'offset', $offset, 'filter_object_id', $filter_object.id )
		item_limit=$limit
	}

</div>