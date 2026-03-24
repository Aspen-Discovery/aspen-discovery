{strip}
	{if !empty($subCategories)}
		{*{$subCategories|@debug_print_var}*}
		<div role="tablist" class="manual" aria-labelledby="tablist-browse-category-{$parentTextId}">
			{foreach from=$subCategories item=subCategory}
			<div class="btn-group" style="margin-right: .5em">
				<button class="btn btn-primary" id="browse-sub-category-tab-{$subCategory.textId}" type="button" role="tab" aria-controls="tabpanel-{$subCategory.textId}" onclick="AspenDiscovery.Browse.changeBrowseSubCategoryTab('{$subCategory.textId}', '{$parentTextId}')"><span class="focus">{translate text=$subCategory.label isPublicFacing=true}</span></button>
				<button id="selected-browse-more-results-{$subCategory.textId}" onclick="AspenDiscovery.Browse.getMoreSubCategoryResultsLink('{$subCategory.textId}', '{$parentTextId}')" class="btn btn-primary more-browse-sub-category" type="button" role="tab" title="{translate text='View all results for %1%' 1={$subCategory.label} inAttribute=true isPublicFacing=true translateParameters=true }"><i class="fas fa-search"></i></button>
				{if !empty($loggedIn)}
					{assign var="subBrowseCategoryId" value=$subCategory.textId}
					{if $parentTextId == 'system_user_lists' || $parentTextId == 'system_saved_searches'}
						{assign var="subBrowseCategoryId" value="{$parentTextId}_{$subCategory.textId}"}
					{/if}
				<button id="selected-browse-dismiss-{$subCategory.textId}" onclick="AspenDiscovery.Account.dismissBrowseCategory(null, '{$subBrowseCategoryId}')" class="btn btn-primary hide-browse-sub-category" type="button" title="{translate text='Hide Category %1%' 1={$subCategory.label} inAttribute=true isPublicFacing=true}"><i class="fas fa-times" role="button"></i></button>
				{/if}
			</div>
			{/foreach}
		</div>
		{foreach from=$subCategories item=subCategory}
			<div id="tabpanel-{$subCategory.textId}" role="tabpanel" aria-labelledby="browse-sub-category-tab-{$subCategory.textId}" {if $subCategory@iteration != 1}class="is-hidden"{/if}>
				<div class="swiper {if $subCategory@iteration == 1}swiper-first{/if} swiper-sub-browse-category-{$subCategory.textId}" id="swiper-sub-{$subCategory.textId}">
					<div class="swiper-navigation-container">
						<div class="swiper-button-prev"></div>
					</div>
					<div class="swiper-wrapper" id="swiper-sub-browse-category-{$subCategory.textId}">
						<div class="swiper-slide" id="swiper-loading-{$subCategory.textId}" style="height: 200px">
							<i class="fas fa-lg fa-spinner fa-spin"></i>
						</div>
					</div>
					<div class="swiper-navigation-container">
						<div class="swiper-button-next"></div>
					</div>
				</div>
			</div>
		{/foreach}
	{/if}
{/strip}
