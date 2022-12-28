{strip}
<div id="list-{$wrapperId}"{if $display == 'false'} style="display:none"{/if} class="verticalTitleScroller{if $collectionSpotlight->coverSize == 'medium'} mediumScroller{/if}">
	<div id="{$wrapperId}" class="titleScrollerWrapper">
		{if $showCollectionSpotlightTitle || $showViewMoreLink}
			<div id="list-{$wrapperId}Header" class="titleScrollerHeader">
				{if !empty($showCollectionSpotlightTitle) && !empty($scrollerTitle)}
					<span class="listTitle resultInformationLabel">{if !empty($scrollerTitle)}{translate text=$scrollerTitle isPublicFacing=true isAdminEnteredData=true}{/if}</span>
				{/if}
				{if !empty($showViewMoreLink)}
					<div id="titleScrollerViewMore{$scrollerName}" class="titleScrollerViewMore"><a href="{$fullListLink}">{translate text="View More" isPublicFacing=true}</a></div>
				{/if}
			</div>
		{/if}
		<div id="titleScroller{$scrollerName}" class="titleScrollerBody">
			<div class="scrollerButtonUp btn btn-primary" onclick="{$scrollerVariable}.scrollToLeft();" aria-label="{translate text="Scroll Up" inAttribute=true isPublicFacing=true}"><i class="glyphicon glyphicon-chevron-up"></i></div>
			<div class="scrollerBodyContainer">
				<div class="scrollerBody" style="display:none"></div>
				<div class="scrollerLoadingContainer">
					<img id="scrollerLoadingImage{$scrollerName}" class="scrollerLoading" src="{img filename="loading_large.gif"}" alt="{translate text="Loading..." isPublicFacing=true inAttribute=true}" />
				</div>
			</div>
			<div class="clearer"></div>
			<div class="scrollerButtonDown btn btn-primary" onclick="{$scrollerVariable}.scrollToRight();" aria-label="{translate text="Scroll Down" inAttribute=true isPublicFacing=true}"><i class="glyphicon glyphicon-chevron-down"></i></div>
		</div>
	</div>
</div>
<script type="text/javascript">
	{* touch swiping controls *}
	$(document).ready(function(){ldelim}
		var scrollFactor = 10; {*// swipe size per item to scroll.*}
		$('#titleScroller{$scrollerName} .scrollerBodyContainer')
			.touchwipe({ldelim}
				wipeUp : function(dy){ldelim}
					var scrollInterval = Math.round(dy / scrollFactor);
					{$scrollerVariable}.swipeUp(scrollInterval);
					{rdelim},
				wipeDown: function(dy) {ldelim}
					var scrollInterval = Math.round(dy / scrollFactor);
					{$scrollerVariable}.swipeDown(scrollInterval);
					{rdelim}
			{rdelim});
	{rdelim});
</script>
{/strip}