{strip}
	{if $showComments || $showFavorites || $showEmailThis || $showShareOnExternalSites}
		<div class="result-tools-horizontal btn-toolbar" role="toolbar">
			{* More Info Link, only if we are showing other data *}
			{if $showMoreInfo || $showComments || $showFavorites}
				{if $showMoreInfo !== false}
					<div class="btn-group btn-group-sm">
						<a href="{if $eventUrl}{$eventUrl}{else}{$recordDriver->getMoreInfoLinkUrl()}{/if}" class="btn btn-sm btn-tools" target="_blank"><i class="fas fa-external-link-alt"></i> {translate text="More Info" isPublicFacing=true}</a>
					</div>
				{/if}
			{/if}

			<div class="btn-group btn-group-sm">
				{include file="Events/share-tools.tpl"}
			</div>
		</div>
	{/if}
{/strip}