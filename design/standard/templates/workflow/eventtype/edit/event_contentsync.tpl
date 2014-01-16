<div class="block">
	<label>{'Server URL'|i18n( 'extension/content_sync' )}:</label>
	<input class="halfbox" type="text" name="server_url" value="{if $event.data_text1}{$event.data_text1}{else}http://example.com{/if}" size="" />
</div>

<div class="block">
	<label>{'CLI mode'|i18n( 'extension/content_sync' )}:</label>
	<input type="checkbox" name="cli_mode" value="1" {if $event.data_int1|eq( 1 )}checked="checked"{/if}/>
</div>