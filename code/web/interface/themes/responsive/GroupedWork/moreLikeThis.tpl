{strip}
	{if !empty($recordDriver)}
	<div id="moreLikeThisInfo" style="" class="row">
		<div class="col-sm-12">
			<div class="jcarousel-wrapper moreLikeThisWrapper">
				<div class="jcarousel horizontalCarouselSpotlight" id="moreLikeThisCarousel">
					<div class="loading">{translate text="Loading more titles like this title..." isPublicFacing=true}</div>
				</div>

				<a href="#" class="jcarousel-control-prev" aria-label="{translate text="Previous Item" inAttribute=true isPublicFacing=true}"><i class="fas fa-caret-left {if $isRTL}fa-flip-horizontal{/if}"></i></a>
				<a href="#" class="jcarousel-control-next" aria-label="{translate text="Next Item" inAttribute=true isPublicFacing=true}"><i class="fas fa-caret-right {if $isRTL}fa-flip-horizontal{/if}"></i></a>
			</div>
		</div>
	</div>
	{/if}
{/strip}
