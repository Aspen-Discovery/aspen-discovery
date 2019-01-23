{strip}
	{* Search Box *}
	{if !$horizontalSearchBar}
		{include file="Search/searchbox-home.tpl"}
	{/if}

	{include file="login-sidebar.tpl"}

	{if $recordCount || $sideRecommendations}
		<div id="refineSearch">
			{* Sort the results*}
			{if $recordCount}
				<div id="results-sort-label" class="row sidebar-label"{if $displaySidebarMenu} style="display: none"{/if}>
					<label for="results-sort">{translate text='Sort Results By'}</label>
				</div>
				{* The div below has to be immediately after the div above for the menubar hiding/showing to work *}
				<div class="row"{if $displaySidebarMenu} style="display: none"{/if}>
					<select id="results-sort" name="sort"
					        onchange="document.location.href = this.options[this.selectedIndex].value;" class="input-medium">
						{foreach from=$sortList item=sortData key=sortLabel}
							<option value="{$sortData.sortUrl|escape}"{if $sortData.selected} selected="selected"{/if}>{translate text=$sortData.desc}</option>
						{/foreach}
					</select>
				</div>
			{/if}

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