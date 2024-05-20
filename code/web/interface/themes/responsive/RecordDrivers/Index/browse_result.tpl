{strip}
    {if $accessibleBrowseCategories == '1'}
	<div class="swiper-slide browse-thumbnail {$coverStyle}">
		<a href="{$summUrl}">
			<img src="{$bookCoverUrlMedium}" alt="{$summTitle}" class="{$coverStyle} {if $browseCategoryRatingsMode != 0}ratings-on{/if}" loading="lazy">
			<div class="swiper-lazy-preloader"></div>
		</a>
	</div>
    {else}
	{if $browseMode == '1'}
		<div class="browse-list grid-item {$coverStyle} {if $browseStyle == 'grid'}browse-grid-style col-tn-6 col-xs-6 col-sm-6 col-md-4 col-lg-3{/if}">
			<a href="{$summUrl}" {if !empty($openInNewWindow)}target="_blank" aria-label="{$summTitle} ({translate text='opens in new window' isPublicFacing=true})"{/if} {if !empty($onclick)}onclick="{$onclick}" {/if}>
				<img class="img-responsive" src="{$bookCoverUrl}" alt="{$summTitle}" title="{$summTitle} by {$summAuthor}">
				<div><strong>{$summTitle}</strong></div>
			</a>
		</div>

	{else}{*Default Browse Mode (covers) *}
		<div class="browse-thumbnail grid-item {$coverStyle} {if $browseStyle == 'grid'}col-tn-6 col-xs-4 col-sm-4 col-md-3 col-lg-2{/if}">
			<a href="{$summUrl}" {if !empty($openInNewWindow)}target="_blank" aria-label="{$summTitle} ({translate text='opens in new window' isPublicFacing=true})"{/if} {if !empty($onclick)}onclick="{$onclick}" {/if}>
				<div>
					<img src="{$bookCoverUrlMedium}" alt="{$summTitle}" title="{$summTitle}" class="{$coverStyle} browse-{$browseStyle} {if $browseCategoryRatingsMode != 0}ratings-on{/if}">
				</div>
			</a>
			{if !empty($showRatings) && $browseCategoryRatingsMode != 0}
				{*can't rate events but still want the spacing to match what we have for rated items*}
				<div class="browse-rating">
					<span class="ui-rater-starsOff" style="width:90px; visibility:hidden"></span>
				</div>
			{/if}
		</div>
	{/if}
	{/if}
{/strip}