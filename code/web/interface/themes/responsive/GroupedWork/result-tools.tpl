{strip}
	{if $showFavorites == 1}
		<div class="text-center row">
			<div class="col-xs-12">
				<span onclick="return AspenDiscovery.Account.showSaveToListForm(this, 'GroupedWork', '{$recordDriver->getPermanentId()|escape}');" class="btn btn-sm addtolistlink addToListBtn">{translate text="Add to list" isPublicFacing=true}</span>
			</div>
		</div>
	{/if}
	<div class="text-center row">
		{include file="GroupedWork/share-tools.tpl"}
	</div>
{/strip}