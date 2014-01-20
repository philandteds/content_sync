<div class="context-block">

	<div class="box-header"><div class="box-tc"><div class="box-ml"><div class="box-mr"><div class="box-tl"><div class="box-tr">
		<h1 class="context-title">&nbsp;{'Request details'|i18n( 'extension/content_sync' )}</h1>
		<div class="header-subline"></div>
	</div></div></div></div></div></div>

	<div class="box-ml"><div class="box-mr"><div class="box-content">
		<div class="content-navigation-childlist">
			<div class="block">
				<label>{'ID'|i18n( 'extension/content_sync' )}:</label>
				{$log.id}
			</div>
			<div class="block">
				<label>{'Date'|i18n( 'extension/content_sync' )}:</label>
				{$log.date|datetime( 'custom', '%d.%m.%Y %H:%i:%s' )}
			</div>
			<div class="block">
				<label>{'User'|i18n( 'extension/content_sync' )}:</label>
				{if $log.user}<a href="{$log.user.main_node.url_alias|ezurl( 'no' )}" target="_blank">{$log.user.name|wash}</a>{else}{'doesn`t exist'|i18n( 'extension/content_sync' )}{/if}
			</div>
			<div class="block">
				<label>{'Object'|i18n( 'extension/content_sync' )}:</label>
				{if $log.object}<a href="{$log.object.main_node.url_alias|ezurl( 'no' )}" target="_blank">{$log.object.name|wash}</a>{else}{'doesn`t exist'|i18n( 'extension/content_sync' )}{/if}
			</div>
			<div class="block">
				<label>{'Version'|i18n( 'extension/content_sync' )}:</label>
				{if $log.object}<a href="{concat( '/content/versionview/', $log.object_id, '/', $log.object_version, '/', $log.version.initial_language.locale )|ezurl( 'no' )}" target="_blank">{$log.object_version} ({$log.version.initial_language.locale})</a>{else}{$log.object_version}{/if}
			</div>
			<div class="block">
				<label>{'Object data'|i18n( 'extension/content_sync' )}:</label>
				<pre>{$log.object_data|wash|explode( ' ' )|implode( '&nbsp;' )}</pre>
			</div>
			<div class="block">
				<label>{'Status'|i18n( 'extension/content_sync' )}:</label>
				{$log.status_description}
			</div>
			<div class="block">
				<label>{'Result'|i18n( 'extension/content_sync' )}:</label>
				{$log.result}
			</div>
			<div class="block">
				<label>{'Error'|i18n( 'extension/content_sync' )}:</label>
				{if $log.error}{$log.error}{else}{'No'|i18n( 'extension/content_sync' )}{/if}
			</div>
			<div class="block">
				<label>{'Import time'|i18n( 'extension/content_sync' )}:</label>
				{$log.import_time}
			</div>
		</div>
	</div></div></div>

</div>