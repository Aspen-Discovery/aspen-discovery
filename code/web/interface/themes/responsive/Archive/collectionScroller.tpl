{strip}
	<div class="nopadding col-sm-12">
		<div class="exhibitPage exploreMoreBar row">{* exhibitPage class overides some exploreMoreBar css*}
			{*<div class="label-left">*}
			<div class="label-top">
				<div class="exploreMoreBarLabel"><div class="archiveComponentHeader">{$browseCollectionTitlesData.title}</div></div>
			</div>

			<div class="exploreMoreContainer">
				<div class="jcarousel-wrapper">
					{* Scrolling Buttons *}
					<a href="#" class="jcarousel-control-prev"{* data-target="-=1"*}><i class="glyphicon glyphicon-chevron-left"></i></a>
					<a href="#" class="jcarousel-control-next"{* data-target="+=1"*}><i class="glyphicon glyphicon-chevron-right"></i></a>

					<div class="exploreMoreItemsContainer jcarousel">
						<ul>
							{foreach from=$browseCollectionTitlesData.collectionTitles item=titleInfo}
								<li class="explore-more-option">
									<figure class="thumbnail" title="{$titleInfo.title|escape}">
										<div class="explore-more-image">
											<a href='{$titleInfo.link}'{if $titleInfo.isExhibit} onclick="AspenDiscovery.Archive.setForExhibitInAExhibitNavigation('{$browseCollectionTitlesData.collectionPid}')" {/if} {*{if $titleInfo.onclick}onclick="{$titleInfo.onclick}"{/if}*}>
												<img src="{$titleInfo.image}" alt="{$titleInfo.title|escape}">
											</a>
										</div>
										<figcaption class="explore-more-category-title">
											<strong>{$titleInfo.title}</strong>
										</figcaption>
									</figure>
								</li>
							{/foreach}
						</ul>
					</div>
				</div>
			</div>
		</div>
	</div>
{/strip}