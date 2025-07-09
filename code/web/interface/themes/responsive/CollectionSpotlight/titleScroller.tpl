{strip}
<div id="tab-{$wrapperId}"{if !empty($display) && $display == 'false'} style="display:none"{/if} class="titleScroller {$tabClasses}{if !empty($collectionSpotlight) && $collectionSpotlight->coverSize == 'medium'} mediumScroller{/if}{if !empty($collectionSpotlight) && $collectionSpotlight->showRatings} scrollerWithRatings{/if}">
	<div id="{$wrapperId}" class="titleScrollerWrapper">
		{if (!empty($showCollectionSpotlightTitle))}
			<div id="tab-{$wrapperId}Header" class="titleScrollerHeader">
				{if !empty($showCollectionSpotlightTitle)}
					<span class="listTitle resultInformationLabel">{if !empty($scrollerTitle)}{translate text=$scrollerTitle isPublicFacing=true isAdminEnteredData=true}{/if}</span>
				{/if}
			</div>
		{/if}
		<div id="titleScroller{$scrollerName}" class="titleScrollerBody">
			<div class="leftScrollerButton enabled btn" onclick="{$scrollerVariable}.scrollToLeft();" aria-label="{translate text="Scroll Left" inAttribute=true isPublicFacing=true}"><i class="glyphicon glyphicon-chevron-left"></i></div>
			<div class="rightScrollerButton btn" onclick="{$scrollerVariable}.scrollToRight();" aria-label="{translate text="Scroll Right" inAttribute=true isPublicFacing=true}"><i class="glyphicon glyphicon-chevron-right"></i></div>
			<div class="scrollerBodyContainer">
				<div class="scrollerBody" style="display:none"></div>
				<div class="scrollerLoadingContainer">
					<img id="scrollerLoadingImage{$scrollerName}" class="scrollerLoading" src="{img filename="loading_large.gif"}" alt="{translate text="Loading..." isPublicFacing=true inAttribute=true}" />
				</div>
			</div>
			<div class="clearer"></div>
			{if !empty($collectionSpotlight) && $collectionSpotlight->showTitle}
				<div id="titleScrollerSelectedTitle{$scrollerName}" class="titleScrollerSelectedTitle notranslate"></div>
			{/if}
			{if !empty($collectionSpotlight) && $collectionSpotlight->showAuthor}
				<div id="titleScrollerSelectedAuthor{$scrollerName}" class="titleScrollerSelectedAuthor notranslate"></div>
			{/if}
		</div>
		{if !empty($showViewMoreLink) && strlen($fullListLink) > 0}
			<div id="titleScrollerViewMore{$scrollerName}" class="titleScrollerViewMore"><a href="{$fullListLink}">{translate text="View More" isPublicFacing=true}</a></div>
		{/if}
	</div>
</div>
<script type="text/javascript">
{*//	 touch swiping controls *}
	$(document).ready(function(){ldelim}
		var scrollFactor = 10; {*// swipe size per item to scroll.*}
		$('#titleScroller{$scrollerName} .scrollerBodyContainer')
			.touchwipe({ldelim}
				wipeLeft : function(dx){ldelim}
					var scrollInterval = Math.round(dx / scrollFactor); {*// vary scroll interval based on wipe length *}
					{$scrollerVariable}.swipeToLeft(scrollInterval);
				{rdelim},
				wipeRight: function(dx) {ldelim}
					var scrollInterval = Math.round(dx / scrollFactor); {*// vary scroll interval based on wipe length *}
					{$scrollerVariable}.swipeToRight(scrollInterval);
				{rdelim}
		{rdelim});
	{rdelim});
</script>
{/strip}