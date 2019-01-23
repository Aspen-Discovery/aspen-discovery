{strip}
	{* New Search Box *}
	{if !$horizontalSearchBar}
		{include file="Search/searchbox-home.tpl"}
	{/if}

	{include file="login-sidebar.tpl"}

	{*{if $recordCount || $sideRecommendations}*}
	{if $sideRecommendations} {* Since Nashville sorting is moved out of sidebar, don't check recordCount. plb 1-6-2016 *}
		<div id="refineSearch">

			{* Sort the results - moved to results-displayMode-toggle.tpl for Nashville - 2015 07 07 by Jenny *}

			{*TODO: delete this once menu bar is well-implemented *}
			<div id="xs-main-content-insertion-point" class="row"></div>

			{if $enrichment.novelist->similarAuthorCount != 0}
				<div id="similar-authors" class="sidebar-links row"{if $displaySidebarMenu} style="display: none"{/if}>
					<div class="panel">
						<div id="similar-authors-label" class="sidebar-label">
							{translate text="Similar Authors"}
						</div>
						<div class="similar-authors panel-body">
							{foreach from=$enrichment.novelist->authors item=similar}
								<div class="facetValue">
									<a href='{$similar.link}'>{$similar.name}</a>
								</div>
							{/foreach}
						</div>
					</div>
				</div>
			{/if}

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
