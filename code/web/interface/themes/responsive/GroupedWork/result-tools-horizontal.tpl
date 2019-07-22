{strip}
	{if $showComments || $showFavorites || $showEmailThis || $showShareOnExternalSites}
		{if empty($id)}
			{assign var="id" value=$summId}
		{/if}
		<div class="result-tools-horizontal btn-toolbar" role="toolbar">
			{* More Info Link, only if we are showing other data *}
			{if $showMoreInfo || $showComments || $showFavorites}
				{if $showMoreInfo !== false}
					<div class="btn-group btn-group-sm">
						<a href="{if $summUrl}{$summUrl}{else}{$recordDriver->getMoreInfoLinkUrl()}{/if}" class="btn btn-sm ">{translate text="More Info"}</a>
					</div>
				{/if}
				{if $showComments == 1}
					<div class="btn-group btn-group-sm{if $module == 'Search' || ($action == 'MyList' && $module == 'MyAccount')} hidden-xs{/if}">
						{* Hide Review Button for xs views in Search Results & User Lists *}
						<button id="userreviewlink{$id}" class="resultAction btn btn-sm" title="{translate text='Add a Review' inAttribute=true}" onclick="return AspenDiscovery.GroupedWork.showReviewForm(this, '{$id}')">
							{translate text='Add a Review'}
						</button>
					</div>
				{/if}
				{if $showFavorites == 1}
					<div class="btn-group btn-group-sm">
						<button onclick="return AspenDiscovery.GroupedWork.showSaveToListForm(this, '{$id|escape}');" class="btn btn-sm ">{translate text='Add to favorites'}</button>
					</div>
				{/if}
			{/if}

			<div class="btn-group btn-group-sm">
				{include file="GroupedWork/share-tools.tpl"}
			</div>
		</div>
	{/if}
{/strip}