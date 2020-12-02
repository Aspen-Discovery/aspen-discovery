{strip}
	<div id="list-{$wrapperId}"{if $display == 'false'} style="display:none"{/if} class="textListScroller">
		<div id="{$wrapperId}" class="titleScrollerWrapper">
			{if $showCollectionSpotlightTitle || $showViewMoreLink}
				<div id="list-{$wrapperId}Header" class="titleScrollerHeader">
					{if $scrollerTitle}
						<span class="listTitle resultInformationLabel">{if $scrollerTitle}{$scrollerTitle|escape:"html"}{/if}</span>
					{/if}
					{if $showViewMoreLink}
						<div id="titleScrollerViewMore{$scrollerName}" class="titleScrollerViewMore"><a href="{$fullListLink}">View More</a></div>
					{/if}
				</div>
			{/if}
			<div id="titleScroller{$scrollerName}" class="titleScrollerBody">
				<div class="scrollerBodyContainer">
					<div class="scrollerBody" style="display:none"></div>
					<div class="scrollerLoadingContainer">
						<img id="scrollerLoadingImage{$scrollerName}" class="scrollerLoading" src="{img filename="loading_large.gif"}" alt="Loading..." />
					</div>
				</div>
				<div class="clearer"></div>
			</div>
		</div>
	</div>
{/strip}