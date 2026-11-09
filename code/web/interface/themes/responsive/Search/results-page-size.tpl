{if !empty($pageSizeList)}
	<label for="results-page-size{$idSuffix}"{if !empty($labelClass)} class="{$labelClass}"{else} style="margin-right: .5rem"{/if}>{translate text='Results per page' isPublicFacing=true}</label>
	<select id="results-page-size{$idSuffix}" name="pageSize" onchange="document.location.href = this.options[this.selectedIndex].value;" class="{if !empty($selectClass)}{$selectClass}{else}input-medium{/if}">
		{foreach from=$pageSizeList item=pageSizeData}
			<option value="{$pageSizeData.url|escape}"{if !empty($pageSizeData.selected)} selected="selected"{/if}>{$pageSizeData.desc|escape}</option>
		{/foreach}
	</select>
{/if}
