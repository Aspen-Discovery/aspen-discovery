{strip}
	<div id="tab-{$wrapperId}"{if $display == 'false'} style="display:none"{/if} class="textListScroller">
		<div id="{$wrapperId}" class="titleScrollerWrapper">
			{if (!empty($showCollectionSpotlightTitle) && $showCollectionSpotlightTitle) || $showViewMoreLink}
				<div id="tab-{$wrapperId}Header" class="titleScrollerHeader">
					{if !empty($showCollectionSpotlightTitle) && !empty($scrollerTitle)}
						<span class="listTitle resultInformationLabel">{if !empty($scrollerTitle)}{translate text=$scrollerTitle isPublicFacing=true isAdminEnteredData=true}{/if}</span>
					{/if}
					{if !empty($showViewMoreLink)}
						<div id="titleScrollerViewMore{$scrollerName}" class="titleScrollerViewMore"><a href="{$fullListLink}">{translate text="View More" isPublicFacing=true}</a></div>
					{/if}
				</div>
			{/if}
			<div id="titleScroller{$scrollerName}" class="titleScrollerBody">
				<div class="scrollerBodyContainer">
					<div class="scrollerBody" style="display:none"></div>
					<div class="scrollerLoadingContainer">
						<img id="scrollerLoadingImage{$scrollerName}" class="scrollerLoading" src="{img filename="loading_large.gif"}" alt="{translate text="Loading..." isPublicFacing=true inAttribute=true}" />
					</div>
				</div>
				<div class="clearer"></div>
			</div>
		</div>
	</div>
{/strip}