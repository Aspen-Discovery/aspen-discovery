{strip}
	{* User's viewing mode toggle switch *}
	<div class="row">{* browse styling replicated here *}
		<div class="col-xs-12">
		{if !empty($recordCount)}
			{* <span class="sidebar-label">
				   <label for="results-sort">{translate text='Sort'}</label></span> *}

			<label for="results-sort">{translate text='Sort by' isPublicFacing=true}</label>
			<select id="results-sort" name="sort" onchange="document.location.href = this.options[this.selectedIndex].value;" class="input-medium">
				{foreach from=$sortList item=sortData key=sortLabel}
					<option value="{$sortData.sortUrl|escape}"{if $sortData.selected} selected="selected"{/if}>{translate text=$sortData.desc isPublicFacing=true inAttribute=true}</option>
				{/foreach}
			</select>
		{/if}
		</div>
	</div>
{/strip}