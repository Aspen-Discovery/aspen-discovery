{strip}
	{* User's viewing mode toggle switch *}
	<div class="row">{* browse styling replicated here *}
		{if !empty($recordCount)}
			{* <span class="sidebar-label">
				   <label for="results-sort">{translate text='Sort'}</label></span> *}

			<select id="results-sort" name="sort" aria-label="{translate text='Sort'}" onchange="document.location.href = this.options[this.selectedIndex].value;" class="input-medium">
				{foreach from=$sortList item=sortData key=sortLabel}
					<option value="{$sortData.sortUrl|escape}"{if $sortData.selected} selected="selected"{/if}>{translate text='Sort by ' }{translate text=$sortData.desc}</option>
				{/foreach}
			</select>
		{/if}

		<div id="selected-browse-label">
			<div class="btn-group btn-group-sm" data-toggle="buttons">
				<label for="covers" title="Covers" class="btn btn-sm btn-default"><input onchange="AspenDiscovery.Searches.toggleDisplayMode(this.id)" type="radio" id="covers">
					<span class="thumbnail-icon"></span><span> {translate text=Covers}</span>
				</label>
				<label for="list" title="Lists" class="btn btn-sm btn-default"><input onchange="AspenDiscovery.Searches.toggleDisplayMode(this.id)" type="radio" id="list">
					<span class="list-icon"></span><span> {translate text=List}</span>
				</label>
			</div>
			<div class="btn-group" id="hideSearchCoversSwitch"{if $displayMode != 'list'} style="display: none;"{/if}>
				<label for="hideCovers" class="checkbox{* control-label*}"> {translate text='Hide Covers'}
					<input id="hideCovers" type="checkbox" onclick="AspenDiscovery.Account.toggleShowCovers(!$(this).is(':checked'))" {if $showCovers == false}checked="checked"{/if}>
				</label>
			</div>
		</div>
	</div>
{/strip}