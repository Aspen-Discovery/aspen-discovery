{strip}
	{if $showComments || $showFavorites || $showEmailThis || $showShareOnExternalSites}
		<div class="result-tools-horizontal btn-toolbar" role="toolbar">
			{if $showNotInterested == true}
			{if empty($showCovers)}
			<div class="btn-group" role="group">
				<button id="notInterested{$summId}" style="word-break: keep-all; white-space: inherit"
						class="btn btn-warning btn-sm notInterested"
						title="{translate text="Select if you don't want to see this title recommended to you." inAttribute=true isPublicFacing=true }"
						onclick="return AspenDiscovery.GroupedWork.markNotInterested('{$summId}');">{translate text="Don't Recommend" isPublicFacing=true}</button>
			</div>
			{/if}
			{/if}
			<div class="btn-group" role="group">
			{* More Info Link, only if we are showing other data *}
			{if $showMoreInfo || $showComments || $showFavorites}
				{if $showMoreInfo !== false}
					<a href="{if !empty($summUrl)}{$summUrl}{else}{$recordDriver->getMoreInfoLinkUrl()}{/if}" class="btn btn-sm btn-tools" aria-label="{translate text="More Info for %1% record %2%" 1=$summTitle|escapeCSS 2=$recordDriver->getPermanentId() isPublicFacing=true inAttribute=true}">{translate text="More Info" isPublicFacing=true}</a>
				{/if}
				{if empty($offline) || $enableEContentWhileOffline}
					{if $showComments == 1}
						{* Hide Review Button for xs views in Search Results & User Lists *}
						<button id="userreviewlink{$recordDriver->getPermanentId()}" class="resultAction btn btn-sm btn-tools{if $module == 'Search' || ($action == 'MyList' && $module == 'MyAccount')} hidden-xs{/if}" onclick="return AspenDiscovery.GroupedWork.showReviewForm(this, '{$recordDriver->getPermanentId()}')" onkeypress="return AspenDiscovery.GroupedWork.showReviewForm(this, '{$recordDriver->getPermanentId()}')">
							{translate text='Add a Review' isPublicFacing=true}
						</button>
					{/if}
					{if $showFavorites == 1}
						<button onclick="return AspenDiscovery.Account.showSaveToListForm(this, 'GroupedWork', '{$recordDriver->getPermanentId()|escape}');" onkeypress="return AspenDiscovery.Account.showSaveToListForm(this, 'GroupedWork', '{$recordDriver->getPermanentId()|escape}');" class="btn btn-sm btn-tools addToListBtn">{translate text="Add to List" isPublicFacing=true}</button>
					{/if}
				{/if}
				{if !empty($loggedIn) && ($module == 'Search' || $module == 'Author') && in_array('Manually Group and Ungroup Works', $userPermissions)}
					<button onclick="return AspenDiscovery.GroupedWork.getGroupWithSearchForm(this, '{$recordDriver->getPermanentId()}', '{$searchId}', '{if empty($page)}1{else}{$page}{/if}')" onkeypress="return AspenDiscovery.GroupedWork.getGroupWithSearchForm(this, '{$recordDriver->getPermanentId()}', '{$searchId}', '{if empty($page)}1{else}{$page}{/if}')" class="btn btn-sm btn-tools">{translate text='Group With' isAdminFacing=true}</button>
				{/if}
			{/if}
			</div>
			<div class="btn-group btn-group-sm" role="group">
				{include file="GroupedWork/share-tools.tpl"}
			</div>
		</div>
	{/if}
{/strip}
