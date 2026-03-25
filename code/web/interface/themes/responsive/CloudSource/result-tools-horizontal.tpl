{strip}
	{if $showEmailThis || $showShareOnExternalSites}
		<div class="result-tools-horizontal btn-toolbar" role="toolbar">
			{* More Info Link, only if we are showing other data *}
			{if !empty($showMoreInfo)}
				{if $showMoreInfo !== false && !empty($externalUrl)}
					<div class="btn-group btn-group-sm">
						<a href="{$externalUrl}" class="btn btn-sm btn-tools" target="_blank" aria-label="{translate text="Access Online" isPublicFacing=true inAttribute=true} ({translate text="opens in a new window" isPublicFacing=true inAttribute=true})"><i class="fas fa-external-link-alt" role="presentation"></i> {translate text="Access Online" isPublicFacing=true}</a>
					</div>
				{/if}
				{if $showMoreInfo !== false}
					<div class="btn-group btn-group-sm">
						<a href="{if !empty($summUrl)}{$summUrl}{else}{$recordDriver->getLinkUrl()}{/if}" class="btn btn-sm btn-tools" onclick="AspenDiscovery.CloudSource.trackCloudSourceUsage('{$recordDriver->getPermanentId()}')" aria-label="{translate text="More Info" isPublicFacing=true inAttribute=true}">{translate text="More Info" isPublicFacing=true}</a>
					</div>
				{/if}
				{if $showFavorites == 1 && (empty($offline) || $enableEContentWhileOffline)}
					<div class="btn-group btn-group-sm">
						<button onclick="return AspenDiscovery.Account.showSaveToListForm(this, 'CloudSource', '{$recordDriver->getPermanentId()|escape}');" class="btn btn-sm btn-tools addToListBtn">{translate text="Add to List" isPublicFacing=true}</button>
					</div>
				{/if}
			{/if}

			<div class="btn-group btn-group-sm">
				{include file="CloudSource/share-tools.tpl"}
			</div>
		</div>
	{/if}
{/strip}
