{strip}
	{if $showComments || $showFavorites || $showEmailThis || $showShareOnExternalSites}
		<div class="result-tools-horizontal btn-toolbar" role="toolbar">
			{* More Info Link, only if we are showing other data *}
			{if $showMoreInfo || $showComments || $showFavorites}
				{if $showMoreInfo !== false}
					<div class="btn-group btn-group-sm">
						<a href="{if !empty($eventUrl)}{$eventUrl}{else}{$recordDriver->getMoreInfoLinkUrl()}{/if}" class="btn btn-sm btn-tools" target="_blank"><i class="fas fa-external-link-alt"></i> {translate text="More Info" isPublicFacing=true}</a>
						<button onclick="return AspenDiscovery.Account.showSaveToListForm(this, 'Events', '{$recordDriver->getUniqueID()|escape}');" class="btn btn-sm addtolistlink addToListBtn">{translate text="Add to list" isPublicFacing=true}</button>
					</div>
				{/if}
			{else}
				<div class="btn-group btn-group-sm">
					<button onclick="return AspenDiscovery.Account.showSaveToListForm(this, 'Events', '{$recordDriver->getUniqueID()|escape}');" class="btn btn-sm addtolistlink addToListBtn">{translate text="Add to list" isPublicFacing=true}</button>
				</div>
			{/if}

			<div class="btn-group btn-group-sm">
				{include file="Events/share-tools.tpl"}
			</div>
		</div>
	{/if}
{/strip}