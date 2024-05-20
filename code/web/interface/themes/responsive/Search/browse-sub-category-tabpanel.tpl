{strip}
    {if !empty($subCategories)}
        {*{$subCategories|@debug_print_var}*}
        {foreach from=$subCategories item=subCategory}
	        <div id="tabpanel-{$subCategory.textId}" role="tabpanel" aria-labelledby="browse-sub-category-tab-{$subCategory.textId}"></div>
        {/foreach}
    {/if}
{/strip}