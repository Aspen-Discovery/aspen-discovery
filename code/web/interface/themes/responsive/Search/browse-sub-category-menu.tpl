{strip}
	{if $subCategories}
		{*{$subCategories|@debug_print_var}*}
		{foreach from=$subCategories item=subCategory}
			<button id="browse-sub-category-{$subCategory.textId}" class="btn btn-small btn-default" data-sub-category-id="{$subCategory.textId}" onclick="return AspenDiscovery.Browse.changeBrowseSubCategory('{$subCategory.textId}')">{translate text=$subCategory.label isPublicFacing=true}</button>
		{/foreach}
	{/if}
{/strip}