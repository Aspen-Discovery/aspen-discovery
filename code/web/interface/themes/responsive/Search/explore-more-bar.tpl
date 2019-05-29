{strip}
	{* TODO: Consider renaming classes to assume they are under the exploreMoreBar class *}
<div class="exploreMoreBar row">
	{*<div class="label-left">*}
	<div class="label-top">
		<img src="{img filename='/interface/themes/responsive/images/ExploreMore.png'}" alt="{translate text='Explore More'}">
		<div class="exploreMoreBarLabel">{translate text='Explore More'}</div>
	</div>

	<div class="exploreMoreContainer">
		<div class="jcarousel-wrapper">
			{* Scrolling Buttons *}
			<a href="#" class="jcarousel-control-prev"{* data-target="-=1"*}><i class="glyphicon glyphicon-chevron-left"></i></a>
			<a href="#" class="jcarousel-control-next"{* data-target="+=1"*}><i class="glyphicon glyphicon-chevron-right"></i></a>

			<div class="exploreMoreItemsContainer jcarousel"{* data-wrap="circular" data-jcarousel="true"*}> {* noIntialize is a filter for VuFind.initCarousels() *}
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
										<a href='{$exploreMoreCategory.link}'>
											<img src="{$exploreMoreCategory.image}" alt="{$exploreMoreCategory.label|escape}">
										</a>
									</div>
									<figcaption class="explore-more-category-title">
										<strong>{$exploreMoreCategory.label|truncate:30}</strong>
									</figcaption>
								</figure>
							</li>
						{/if}
					{/foreach}
				</ul>
			</div>
		</div>
	</div>

</div>
{/strip}

