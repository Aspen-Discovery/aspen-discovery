{strip}
	{* User's viewing mode toggle switch *}
	<div class="row">{* browse styling replicated here *}
		<div class="col-xs-6">
			{if !empty($recordCount)}
				{* <span class="sidebar-label">
					   <label for="results-sort">{translate text='Sort'}</label></span> *}
				<label for="results-sort">{translate text='Sort by' isPublicFacing=true}</label>
				<select id="results-sort" name="sort" onchange="document.location.href = this.options[this.selectedIndex].value;" class="input-medium">
					{foreach from=$sortList item=sortData key=sortLabel}
						<option value="{$sortData.sortUrl|escape}"{if !empty($sortData.selected)} selected="selected"{/if}>{translate text=$sortData.desc isPublicFacing=true inAttribute=true}</option>
					{/foreach}
				</select>
			{/if}
		</div>
		<div class="col-xs-6">
			<div id="selected-browse-label">
				<div class="btn-group" id="hideSearchCoversSwitch">
					<label for="hideCovers" class="checkbox{* control-label*}"> {translate text='Hide Covers' isPublicFacing=true}
						<input id="hideCovers" type="checkbox" onclick="AspenDiscovery.Account.toggleShowCovers(!$(this).is(':checked'))" {if $showCovers == false}checked="checked"{/if}>
					</label>
				</div>
			</div>
		</div>
	</div>
{/strip}