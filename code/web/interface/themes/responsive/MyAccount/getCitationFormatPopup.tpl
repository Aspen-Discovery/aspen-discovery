<div align="left">
	{if $message}<div class="error">{$message|translate}</div>{/if}

	<form action="/MyAccount/CiteList" method="get" class="form" id="citeListForm">
		<input type="hidden" name="listId" value="{$listId|escape}">
		<div class="form-group">
			<label for="citationFormat">{translate text='Citation Format'}:</label>
			<select name="citationFormat" id="citationFormat" class="form-control">
				{foreach from=$citationFormats item=formatName key=format}
					<option value="{$format}">{$formatName}</option>
				{/foreach}
			</select>
		</div>

	</form>
</div>