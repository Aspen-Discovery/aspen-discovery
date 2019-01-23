{* Checkbox filters*}
<table>
	{foreach from=$checkboxFilters item=current}
		<tr{if $recordCount < 1 && !$current.selected} style="display: none;"{/if}>
			<td style="vertical-align:top; padding: 3px;">
				<input type="checkbox" name="filter[]" value="{$current.filter|escape}"
				       {if $current.selected}checked="checked"{/if}
				       onclick="document.location.href='{$current.toggleUrl|escape}';" />
			</td>
			<td>
				{translate text=$current.desc}<br />
			</td>
		</tr>
	{/foreach}
</table>