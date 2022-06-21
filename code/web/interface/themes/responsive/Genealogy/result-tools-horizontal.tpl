{strip}
	{if $showEmailThis || $showShareOnExternalSites}
		<div class="result-tools-horizontal btn-toolbar" role="toolbar">
			{* More Info Link, only if we are showing other data *}
			{if $showMoreInfo}
				{if $showMoreInfo !== false}
					<div class="btn-group btn-group-sm">
						<a href="{if $summUrl}{$summUrl}{else}{$recordDriver->getLinkUrl()}{/if}" class="btn btn-sm btn-tools">{translate text="More Info" isPublicFacing=true}</a>
					</div>
				{/if}
				{if $showFavorites == 1}
					<div class="btn-group btn-group-sm">
						<button onclick="return AspenDiscovery.Account.showSaveToListForm(this, 'Genealogy', '{$recordDriver->getPermanentId()|escape}');" class="btn btn-sm btn-tools">{translate text="Add to list" isPublicFacing=true}</button>
					</div>
				{/if}
			{/if}

			<div class="btn-group btn-group-sm">
				{include file="Genealogy/share-tools.tpl"}
			</div>
		</div>
	{/if}
{/strip}