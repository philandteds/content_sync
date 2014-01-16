<div class="block">
	<label>{'Server URL'|i18n( 'extension/content_sync' )}:</label>
	<a href="{$event.data_text1}">{$event.data_text1}</a>
</div>

<div class="block">
	<label>{'CLI mode'|i18n( 'extension/content_sync' )}:</label>
	{if $event.data_int1|eq( 1 )}{'Yes'|i18n( 'extension/content_sync' )}{else}{'No'|i18n( 'extension/content_sync' )}{/if}
</div>