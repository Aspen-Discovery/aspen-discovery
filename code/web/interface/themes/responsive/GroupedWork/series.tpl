{strip}
	{if !empty($recordDriver)}
	<div class="seriesLoadingNote">{translate text="Checking series information..." isPublicFacing=true}</div>
	<div id="seriesInfo" style="display:none" class="row">
		<div class="col-sm-12">
			<div class="jcarousel-wrapper seriesWrapper">
				<div class="jcarousel horizontalCarouselSpotlight" id="seriesCarousel">
					<div class="loading">{translate text="Loading titles in this series..." isPublicFacing=true}</div>
				</div>

				<a href="#" class="jcarousel-control-prev" aria-label="{translate text="Previous Item" inAttribute=true isPublicFacing=true}"><i class="fas fa-caret-left"></i></a>
				<a href="#" class="jcarousel-control-next" aria-label="{translate text="Next Item" inAttribute=true isPublicFacing=true}"><i class="fas fa-caret-right"></i></a>
			</div>
		</div>
	</div>
	{/if}
{/strip}