{strip}
	<div id="scrollerTitle{$listName}{$key}" class="carouselScrollerTitle">
		<a href="{$titleURL}">
			<div class="carouselScrollerTitleImage">
				<img src="{$imageUrl}" class="scrollerTitleCover" alt="{$title} Cover"/>
			</div>
			<div class="carouselScrollerTitleLabel">
				{if $collectionSpotlight->showTitle}
					<span>{$title}</span>
				{/if}
				{if $collectionSpotlight->showAuthor}
					<span>by {$author}</span>
				{/if}
			</div>
		</a>
		{* show ratings check in the template *}
		{include file="GroupedWork/title-rating.tpl" showNotInterested=false}
	</div>
{/strip}