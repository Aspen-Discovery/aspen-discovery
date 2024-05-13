{strip}
	{* TODO: Consider renaming classes to assume they are under the exploreMoreBar class *}
{if !empty($showExploreMoreOptions)}
<div class="exploreMoreBar row">
	{*<div class="label-left">*}
	<div class="label-top">
		<div class="exploreMoreBarLabel">
			{if $userLang->isRTL()} <!-- Fixed by Kware -->
				<i class="fas fa-share-square fa-flip-horizontal fa-x2"></i>
			{else}
				<i class="fas fa-share-square fa-x2"></i>
			{/if} {translate text='Explore More' isPublicFacing=true}
		</div>
	</div>

	<div class="exploreMoreContainer">
		<div class="jcarousel-wrapper">
			{* Scrolling Buttons *}
			<a href="#" class="jcarousel-control-prev"{* data-target="-=1"*} aria-label="Previous"><i class="glyphicon glyphicon-chevron-left"></i></a>
			<a href="#" class="jcarousel-control-next"{* data-target="+=1"*} aria-label="Next"><i class="glyphicon glyphicon-chevron-right"></i></a>

			<div class="exploreMoreItemsContainer jcarousel">
				<ul>
					{foreach from=$exploreMoreOptions item=exploreMoreCategory}
						{if !empty($exploreMoreCategory.placeholder)}
							<li class="">
								<a href='{$exploreMoreCategory.link}'>
									<img src="{$exploreMoreCategory.image}" alt="{$exploreMoreCategory.label|escape}">
								</a>
							</li>
						{else}
							<li class="explore-more-option">
								<figure class="thumbnail" title="{$exploreMoreCategory.label|escape}">
									<div class="explore-more-image">
										<a href='{$exploreMoreCategory.link}' {if !empty($exploreMoreCategory.onclick)}onclick="{$exploreMoreCategory.onclick}"{/if} {if !empty($exploreMoreCategory.openInNewWindow)}target="_blank" aria-label="{translate text="Explore more on"  inAttribute=true isPublicFacing=true} {$exploreMoreCategory.label|escapeCSS} ({translate text="opens in a new window" isPublicFacing=true inAttribute=true})"{/if}>
											<img src="{$exploreMoreCategory.image}" alt="{translate text="Explore more on"  inAttribute=true isPublicFacing=true} {$exploreMoreCategory.label|escapeCSS}">
										</a>
										<figcaption class="explore-more-category-title">
											<a href='{$exploreMoreCategory.link}' {if !empty($exploreMoreCategory.onclick)}onclick="{$exploreMoreCategory.onclick}"{/if} {if !empty($exploreMoreCategory.openInNewWindow)}target="_blank" aria-label="{$exporeMoreCategory.label|escape|truncate:30} ({translate text='opens in new window' isPublicFacing=true})" {/if}>
												<strong>{$exploreMoreCategory.label|truncate:30}</strong>
											</a>
										</figcaption>
									</div>
								</figure>
							</li>
						{/if}
					{/foreach}
				</ul>
			</div>
		</div>
	</div>

</div>
{else}
	<div>
	</div>
{/if}
{/strip}

