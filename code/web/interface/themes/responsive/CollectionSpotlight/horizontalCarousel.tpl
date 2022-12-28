<div id="list-{$wrapperId}"{if !empty($display) && $display == 'false'} style="display:none"{/if} class="titleScroller tab-pane{if !empty($active)} active{/if}{if !empty($collectionSpotlight) && $collectionSpotlight->coverSize == 'medium'} mediumScroller{/if}{if !empty($collectionSpotlight) && $collectionSpotlight->showRatings} scrollerWithRatings{/if}">
{if !empty($showCollectionSpotlightTitle)}
	<div id="list-{$wrapperId}Header" class="titleScrollerHeader">
		{if !empty($showCollectionSpotlightTitle) && !empty($scrollerTitle)}
			<span class="listTitle resultInformationLabel">{if !empty($scrollerTitle)}{translate text=$scrollerTitle isPublicFacing=true isAdminEnteredData=true}{/if}</span>
		{/if}
	</div>
{/if}
<div class="jcarousel-wrapper horizontalCarouselSpotlightWrapper">
	<div class="jcarousel horizontalCarouselSpotlight {if $collectionSpotlight->coverSize == 'medium'}mediumScroller{/if}" id="collectionSpotlightCarousel{$list->id}">
		<div class="loading">{translate text="Loading carousel items..." isPublicFacing=true }</div>
	</div>

	<a href="#" class="jcarousel-control-prev" aria-label="{translate text="Previous Item" isPublicFacing=true inAttribute=true}"><i class="fas fa-caret-left"></i></a>
	<a href="#" class="jcarousel-control-next" aria-label="{translate text="Next Item" isPublicFacing=true inAttribute=true}"><i class="fas fa-caret-right"></i></a>

	{if !empty($showViewMoreLink)}
		<div id="titleScrollerViewMore{$scrollerName}" class="titleScrollerViewMore"><a href="{$fullListLink}">{translate text="View More" isPublicFacing=true}{if !empty($showViewMoreListTitle)} {translate text="$showViewMoreListTitle" isPublicFacing=true}{/if}</a></div>
	{/if}
</div>
<script type="text/javascript">
	$(document).ready(function(){ldelim}
		AspenDiscovery.CollectionSpotlights.loadCarousel('{$list->id}', '/Search/AJAX?method=getSpotlightTitles&id={$list->id}&scrollerName={$listName}&coverSize={$collectionSpotlight->coverSize}&showRatings={$collectionSpotlight->showRatings}&numTitlesToShow={$collectionSpotlight->numTitlesToShow}{if !empty($reload)}&reload=true{/if}');
	{rdelim});
</script>
</div>