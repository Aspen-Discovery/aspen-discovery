{strip}
	{if !empty($subCategories)}
		{*{$subCategories|@debug_print_var}*}
		{foreach from=$subCategories item=subCategory}
			<button tabindex="0" id="browse-sub-category-{$subCategory.textId}" class="btn btn-small btn-default" data-sub-category-id="{$subCategory.textId}" onclick="return AspenDiscovery.Browse.changeBrowseSubCategory('{$subCategory.textId}')">{translate text=$subCategory.label isPublicFacing=true}</button>
			{if !empty($subCategory.searchUrl)}
				<a class="browse-sub-category-search-link" href="{$subCategory.searchUrl}" title="{translate text="View all results for %1%" 1=$subCategory.label translateParameters=true isPublicFacing=true inAttribute=true}">
					<i class="fas fa-search" role="presentation"></i>
				</a>
			{/if}
		{/foreach}
	{/if}
{/strip}
