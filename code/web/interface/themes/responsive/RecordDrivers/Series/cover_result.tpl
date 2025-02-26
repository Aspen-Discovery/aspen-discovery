{strip}
	{if $accessibleBrowseCategories == '1' && $action != 'Results' && !$isForSearchResults}
		<div class="swiper-slide browse-thumbnail {$coverStyle}">
			<a href="{$summUrl}">
				<img src="{$bookCoverUrlMedium}" alt="{$summTitle|escape}" class="{$coverStyle}" loading="lazy">
				<div class="swiper-lazy-preloader"></div>
			</a>
		</div>
	{else}
		{if !empty($browseMode) && $browseMode == '1'}
			<div class="browse-list grid-item {$coverStyle} col-tn-12 col-xs-6 col-sm-6 col-md-4 col-lg-3">
				<a  href="{$summUrl}">
					{if !empty($isNew)}<span class="browse-cover-badge">{translate text="New!" isPublicFacing=true}</span> {/if}
					<img class="img-responsive {$coverStyle} browse-{$browseStyle}" src="{$bookCoverUrl}" alt="{$summTitle} by {implode subject=$summAuthor glue=", "}" title="{$summTitle} by {implode subject=$summAuthor glue=", "}">
					<div><strong>{$summTitle}</strong><br> by {implode subject=$summAuthor glue=", "}</div>
				</a>
			</div>
		{else}{*Default Browse Mode (covers) *}
			<div class="browse-thumbnail grid-item {$coverStyle} {if $browseStyle == 'grid'}col-tn-6 col-xs-4 col-sm-4 col-md-3 col-lg-2{/if}">
				<a href="{$summUrl}">
					{*  TODO: add pop-up for list *}
					<div>
						{if !empty($isNew)}<span class="browse-cover-badge">{translate text="New!" isPublicFacing=true}</span> {/if}
						<img src="{$bookCoverUrlMedium}" alt="{$summTitle} by {implode subject=$summAuthor glue=", "}" title="{$summTitle} by {implode subject=$summAuthor glue=", "}" class="{$coverStyle} browse-{$browseStyle}">
					</div>
				</a>
			</div>
		{/if}
	{/if}
{/strip}
