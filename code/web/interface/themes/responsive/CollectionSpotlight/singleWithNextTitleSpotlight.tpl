{strip}
<div id="list-{$wrapperId}" {if $display == 'false'}style="display:none"{/if} class="titleScroller singleTitleWithNextSpotlight {if $collectionSpotlight->coverSize == 'medium'}mediumScroller{/if} {if $collectionSpotlight->showRatings}scrollerWithRatings{/if}">
	<div id="{$wrapperId}" class="titleScrollerWrapper singleTitleSpotlightWrapper">
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
			<div class="rightScrollerButton btn" onclick="{$scrollerVariable}.scrollToRight();">
				<i class="glyphicon glyphicon-chevron-right"></i>
			</div>
			<div class="scrollerBodyContainer">
				<div class="scrollerBody" style="display:none"></div>
				<div class="scrollerLoadingContainer">
					<img id="scrollerLoadingImage{$scrollerName}" class="scrollerLoading" src="{img filename="loading_large.gif"}" alt="{translate text="Loading..." isPublicFacing=true inAttribute=true}" />
				</div>
			</div>
			<div class="clearer"></div>
			{if $collectionSpotlight->showTitle}
				<div id="titleScrollerSelectedTitle{$scrollerName}" class="titleScrollerSelectedTitle"></div>
			{/if}
			{if $collectionSpotlight->showAuthor}
				<div id="titleScrollerSelectedAuthor{$scrollerName}" class="titleScrollerSelectedAuthor"></div>
			{/if}
		</div>
	</div>
</div>
<script type="text/javascript">
	$("#list-" + '{$wrapperId}'+" .rightScrollerButton").button(
		{literal}
		{icons: {primary:'ui-icon-triangle-1-e'}, text: false}
		{/literal}
	);

	{* touch swiping controls *}
	$(document).ready(function(){ldelim}
		$('#titleScroller{$scrollerName} .scrollerBodyContainer')
			.touchwipe({ldelim}
				wipeLeft : function(dx){ldelim}
					{$scrollerVariable}.swipeToLeft(1); {*// scroll single item*}
					{rdelim},
				wipeRight: function(dx) {ldelim}
					{$scrollerVariable}.swipeToRight(1); {*// scroll single item*}
					{rdelim}
				{rdelim});
		{rdelim});
</script>
{/strip}