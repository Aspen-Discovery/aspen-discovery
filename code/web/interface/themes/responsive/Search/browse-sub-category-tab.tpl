{strip}
    {if !empty($subCategories)}
        {*{$subCategories|@debug_print_var}*}
	    <div role="tablist" class="manual" aria-labelledby="tablist-browse-category-{$parentTextId}">
        {foreach from=$subCategories item=subCategory}
			<button class="btn btn-primary" style="margin-bottom: .5em; margin-left: .5em" id="browse-sub-category-tab-{$subCategory.textId}" type="button" role="tab" aria-selected="{if $subCategory@iteration != 1}false{else}true{/if}" aria-controls="tabpanel-{$subCategory.textId}"  {if $subCategory@iteration != 1}tabindex="-1"{/if} onclick="AspenDiscovery.Browse.changeBrowseSubCategoryTab('{$subCategory.textId}', '{$parentTextId}')"><span class="focus">{translate text=$subCategory.label isPublicFacing=true}</span></button>
        {/foreach}
	    </div>
        {foreach from=$subCategories item=subCategory}
		    <div id="tabpanel-{$subCategory.textId}" role="tabpanel" aria-labelledby="browse-sub-category-tab-{$subCategory.textId}" {if $subCategory@iteration != 1}class="is-hidden"{/if}>
			    <div class="swiper {if $subCategory@iteration == 1}swiper-first{/if} swiper-sub-browse-category-{$subCategory.textId}" id="swiper-sub-{$subCategory.textId}">
					<div class="swiper-navigation-container">
						<div class="swiper-button-prev"></div>
					</div>
				    <div class="swiper-wrapper" id="swiper-sub-browse-category-{$subCategory.textId}">
					    {if $subCategory@iteration == 1 && !empty($subCategory.initialResults)}
						    <div class="swiper-slide" id="swiper-loading-{$subCategory.textId}" style="height: 200px">
							    <i class="fas fa-lg fa-spinner fa-spin"></i>
						    </div>
						    <script type="text/javascript">
                                {literal}
							    $(document).ready(function() {
								    AspenDiscovery.Browse.changeBrowseSubCategoryTab({/literal}'{$subCategory.textId}','{$parentTextId}'{literal});
							    });
                                {/literal}
						    </script>
					    {else}
						    <div class="swiper-slide" id="swiper-loading-{$subCategory.textId}" style="height: 200px">
							    <i class="fas fa-lg fa-spinner fa-spin"></i>
						    </div>
                        {/if}
				    </div>
				    <div class="swiper-navigation-container">
				        <div class="swiper-button-next"></div>
				    </div>
			    </div>
		    </div>
        {/foreach}
    {/if}
{/strip}