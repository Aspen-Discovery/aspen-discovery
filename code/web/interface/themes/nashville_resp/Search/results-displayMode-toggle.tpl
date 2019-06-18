{strip}


<div class="row">



{* Sort the results*}{* Moved sort from results-sidebar.tpl to here - JE 6/18/15 *}
	{if !empty($recordCount)}
        <select id="results-sort" name="sort" aria-label="{translate text='Sort by'}" onchange="document.location.href = this.options[this.selectedIndex].value;" class="input-medium">
            {foreach from=$sortList item=sortData key=sortLabel}
                <option value="{$sortData.sortUrl|escape}"{if $sortData.selected} selected="selected"{/if}>{translate text='Sort by'} {translate text=$sortData.desc}</option>
            {/foreach}
        </select>	
	{/if}

{* User's viewing mode toggle switch *}
	<div id="selected-browse-label">{* browse styling replicated here *}
		<div class="btn-group btn-group-sm" data-toggle="buttons">
			<label for="covers" title="Covers" class="btn btn-sm btn-default"><input onchange="AspenDiscovery.Searches.toggleDisplayMode(this.id)" type="radio" id="covers">
				<span class="thumbnail-icon"></span><span> Covers</span>
			</label>
			<label for="list" title="Lists" class="btn btn-sm btn-default"><input onchange="AspenDiscovery.Searches.toggleDisplayMode(this.id);" type="radio" id="list">
				<span class="list-icon"></span><span> List</span>
			</label>
		</div>
	</div>
</div>
{/strip}