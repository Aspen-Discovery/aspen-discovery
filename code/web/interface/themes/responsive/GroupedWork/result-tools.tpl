{strip}
	{if $showFavorites == 1}
		<div class="text-center row">
			<div class="col-xs-12">
				{*<button onclick="return VuFind.GroupedWork.showSaveToListForm(this, '{$recordDriver->getPermanentId()|escape}');" class="btn btn-sm addtolistlink" type="button">{translate text='Add to favorites'}</button>*}
				<span onclick="return VuFind.GroupedWork.showSaveToListForm(this, '{$recordDriver->getPermanentId()|escape}');" class="btn btn-sm addtolistlink">{translate text='Add to favorites'}</span>
			</div>
		</div>
	{/if}
	<div class="text-center row">
		{include file="GroupedWork/share-tools.tpl"}
	</div>
{/strip}