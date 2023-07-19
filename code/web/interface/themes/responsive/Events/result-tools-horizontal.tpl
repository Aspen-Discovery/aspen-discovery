{strip}
	{if $showComments || $showFavorites || $showEmailThis || $showShareOnExternalSites}
		<div class="result-tools-horizontal btn-toolbar" role="toolbar">
			{* More Info Link, only if we are showing other data *}
			{if $showMoreInfo || $showComments || $showFavorites}
				{if $showMoreInfo !== false}
					<div class="btn-group btn-group-sm">
						{if $bypassEventPage}
							<a href="{$recordDriver->getExternalUrl()}" class="btn btn-sm btn-tools" target="_blank"><i class="fas fa-external-link-alt"></i> {translate text="More Info" isPublicFacing=true}</a>
						{else}
							<a href="{if !empty($eventUrl)}{$eventUrl}{else}{$recordDriver->getExternalUrl()}{/if}" class="btn btn-sm btn-tools">{translate text="More Info" isPublicFacing=true}</a>
						{/if}
						{if $isStaffWithPermissions && $eventsInLists == 1 || $eventsInLists == 2}
							<button onclick="return AspenDiscovery.Account.showSaveToListForm(this, 'Events', '{$recordDriver->getUniqueID()|escape}');" class="btn btn-sm btn-tools addToListBtn">{translate text="Add to List" isPublicFacing=true}</button>
						{/if}
					</div>
				{/if}
			{else}
				{if $isStaffWithPermissions && $eventsInLists == 1 || $eventsInLists == 2}
					<div class="btn-group btn-group-sm">
						<button onclick="return AspenDiscovery.Account.showSaveToListForm(this, 'Events', '{$recordDriver->getUniqueID()|escape}');" class="btn btn-sm btn-tools addToListBtn">{translate text="Add to List" isPublicFacing=true}</button>
					</div>
				{/if}
			{/if}

			<div class="btn-group btn-group-sm">
				{include file="Events/share-tools.tpl"}
			</div>
		</div>
	{/if}
{/strip}