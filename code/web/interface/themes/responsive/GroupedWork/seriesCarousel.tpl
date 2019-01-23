{strip}
	<div class="jcarousel-wrapper seriesCarousel">
		<div class="jcarousel">
			<ul>
				{foreach from=$titles item=title}
					<li class="carousel-title">
						<a href="{$path}/GroupedWork/{$title.id}">
							<img src="{$title.image}" class="scrollerTitleCover" alt="{$title.title}" title="{$title.title}"/>
						</a>
					</li>
				{/foreach}
			</ul>
		</div>

		<a href="#" class="jcarousel-control-prev">&lsaquo;</a>
		<a href="#" class="jcarousel-control-next">&rsaquo;</a>

		<p class="jcarousel-pagination"></p>
	</div>
{/strip}