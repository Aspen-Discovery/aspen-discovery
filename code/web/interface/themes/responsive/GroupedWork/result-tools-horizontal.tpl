{strip}
	{if $showComments || $showFavorites || $showEmailThis || $showShareOnExternalSites}
		<div class="result-tools-horizontal btn-toolbar" role="toolbar">
			{* More Info Link, only if we are showing other data *}
			{if $showMoreInfo || $showComments || $showFavorites}
			{if $showMoreInfo !== false}
				<div class="btn-group btn-group-sm">
					<a href="{if $summUrl}{$summUrl}{else}{$recordDriver->getMoreInfoLinkUrl()}{/if}" class="btn btn-sm ">More Info</a>
				</div>
			{/if}
			{*
			<div class="resultAction"><a href="#" class="cart" onclick="return addToBag('{$summId|escape}', '{$summTitle|replace:'"':''|escape:'javascript'}', '{$summShortId}');"><span class="silk cart">&nbsp;</span>{translate text="Add to cart"}</a></div>
			*}
			{if $showComments == 1}
				<div class="btn-group btn-group-sm{if $module == 'Search' || ($action == 'MyList' && $module == 'MyAccount')} hidden-xs{/if}">
					{* Hide Review Button for xs views in Search Results & User Lists *}
					<button id="userreviewlink{$summShortId}" class="resultAction btn btn-sm" title="Add a Review" onclick="return VuFind.GroupedWork.showReviewForm(this, '{$summId}')">
						Add a Review
					</button>
				</div>
			{/if}
			{if $showFavorites == 1}
				<div class="btn-group btn-group-sm">
					<button onclick="return VuFind.GroupedWork.showSaveToListForm(this, '{$summId|escape}');" class="btn btn-sm ">{translate text='Add to favorites'}</button>
				</div>
			{/if}
			{/if}
			{*  TODO: Restore export format functionality.  PK-395.  Looks like RefWorks may still work, but EndNote does not.
					May need to do some work to have export work properly with Works. *}
			{if is_array($exportFormats) && count($exportFormats) > 0}
				{foreach from=$exportFormats item=exportFormat}
					<div class="btn-group btn-group-sm">
					<a {if $exportFormat=="RefWorks"}target="{$exportFormat}Main" {/if}href="{$path}/Record/{$id|escape:"url"}/Export?style={$exportFormat|escape:"url"}">
						<button class="btn btn-sm ">{$exportFormat|escape}</button>
					</a>
					</div>
				{/foreach}
			{/if}

			<div class="btn-group btn-group-sm">
				{include file="GroupedWork/share-tools.tpl"}
			</div>
		</div>
	{/if}
{/strip}