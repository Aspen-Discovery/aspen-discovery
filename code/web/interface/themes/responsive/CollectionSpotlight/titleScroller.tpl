{strip}
<div id="list-{$wrapperId}"{if !empty($display) && $display == 'false'} style="display:none"{/if} class="titleScroller tab-pane{if !empty($active)} active{/if}{if !empty($collectionSpotlight) && $collectionSpotlight->coverSize == 'medium'} mediumScroller{/if}{if !empty($collectionSpotlight) && $collectionSpotlight->showRatings} scrollerWithRatings{/if}">
	<div id="{$wrapperId}" class="titleScrollerWrapper">
		{if !empty($showCollectionSpotlightTitle) || !empty($showViewMoreLink) || !empty($Links)}
			<div id="list-{$wrapperId}Header" class="titleScrollerHeader">
				{if !empty($showCollectionSpotlightTitle)}
					<span class="listTitle resultInformationLabel">{if $scrollerTitle}{$scrollerTitle|escape:"html"}{/if}</span>
				{/if}

				{if !empty($Links)}
					{foreach from=$Links item=link}
						<div class="linkTab">
							<a href='{$link->link}'><span class="seriesLink">{$link->name}</span></a>
						</div>
					{/foreach}
				{elseif !empty($showViewMoreLink) && strlen($fullListLink) > 0}
					<div class="linkTab" style="float:right">
						<a href='{$fullListLink}'><span class="seriesLink">View More</span></a>
					</div>
				{/if}

			</div>
		{/if}
		<div id="titleScroller{$scrollerName}" class="titleScrollerBody">
			<div class="leftScrollerButton enabled btn" onclick="{$scrollerVariable}.scrollToLeft();"><i class="glyphicon glyphicon-chevron-left"></i></div>
			<div class="rightScrollerButton btn" onclick="{$scrollerVariable}.scrollToRight();"><i class="glyphicon glyphicon-chevron-right"></i></div>
			<div class="scrollerBodyContainer">
				<div class="scrollerBody" style="display:none"></div>
				<div class="scrollerLoadingContainer">
					<img id="scrollerLoadingImage{$scrollerName}" class="scrollerLoading" src="{img filename="loading_large.gif"}" alt="Loading..." />
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