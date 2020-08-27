{strip}
	{include file="login-sidebar.tpl"}

	{*{if $recordCount || $sideRecommendations}*}
	{if $sideRecommendations} {* Since Nashville sorting is moved out of sidebar, don't check recordCount. plb 1-6-2016 *}
		<div id="refineSearch">

			{* Sort the results - moved to results-displayMode-toggle.tpl for Nashville - 2015 07 07 by Jenny *}

			{if $enrichment.novelist->getAuthorCount() != 0}
				<div id="similar-authors" class="sidebar-links row">
					<div>
						<div id="similar-authors-label" class="sidebar-label">
							{translate text="Similar Authors"}
						</div>
						<div class="similar-authors">
							{foreach from=$enrichment.novelist->getAuthors() item=similar}
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
