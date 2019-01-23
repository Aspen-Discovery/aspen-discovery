{strip}
	{* New Search Box *}
	{if !$horizontalSearchBar}
		{include file="Search/searchbox-home.tpl"}
	{/if}

	{include file="login-sidebar.tpl"}

	{*{if $recordCount || $sideRecommendations}*}
	{if $sideRecommendations} {* Since Nashville sorting is moved out of sidebar, don't check recordCount. plb 1-6-2016 *}
		<div id="refineSearch">

			{* Hid sort and copied it to results-displayMode-toggle.tpl - JE 6/18/15 *}
			{* Sort the results*}
			{*	{if $recordCount}
					<div id="results-sort-label" class="row sidebar-label">
						<label for="results-sort">{translate text='Sort Results By'}</label>
					</div>

					<div class="row">
						<select id="results-sort" name="sort" onchange="document.location.href = this.options[this.selectedIndex].value;" class="input-medium">
							{foreach from=$sortList item=sortData key=sortLabel}
								<option value="{$sortData.sortUrl|escape}"{if $sortData.selected} selected="selected"{/if}>{translate text=$sortData.desc}</option>
							{/foreach}
						</select>
					</div>
				{/if}
			*}
			{*TODO: delete this once menu bar is well-implemented *}
			<div id="xs-main-content-insertion-point" class="row"></div>

			{* Narrow Results *}
			{if $sideRecommendations}
				<div class="row">
					{foreach from=$sideRecommendations item="recommendations"}
						{include file=$recommendations}
					{/foreach}
				</div>
			{/if}
		</div>
	{/if}

	{if $loggedIn}
		{* Account Menu *}
		{include file="MyAccount/menu.tpl"}
	{/if}

	{include file="library-sidebar.tpl"}
{/strip}