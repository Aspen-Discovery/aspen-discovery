{strip}
	{* User's viewing mode toggle switch *}
	<div class="row">{* browse styling replicated here *}
		{if !empty($recordCount)}
			{* <span class="sidebar-label">
				   <label for="results-sort">{translate text='Sort'}</label></span> *}

			<select id="results-sort" name="sort" aria-label="{translate text='Sort'}" onchange="document.location.href = this.options[this.selectedIndex].value;" class="input-medium">
				{foreach from=$sortList item=sortData key=sortLabel}
					<option value="{$sortData.sortUrl|escape}"{if $sortData.selected} selected="selected"{/if}>{translate text='Sort by'} {translate text=$sortData.desc}</option>
				{/foreach}
			</select>
		{/if}
	</div>
{/strip}