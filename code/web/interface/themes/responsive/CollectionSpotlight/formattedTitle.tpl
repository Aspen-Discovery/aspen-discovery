{strip}
	<div id="scrollerTitle{$listName}{$key}" class="scrollerTitle">
		<a href="{$titleURL}" id="descriptionTrigger{$shortId}">
		<img src="{$imageUrl}" class="scrollerTitleCover" alt="{translate text="%1% Cover" 1=$title isPublicFacing=true inAttribute=true}"/>
		</a>
		{if !empty($showRatings)}
			{include file="GroupedWork/title-rating.tpl" id=$id summId=$id ratingData=$ratingData showNotInterested=$showNotInterested}
		{/if}
	</div>
	<div id="descriptionPlaceholder{$id}" style="display:none" class="loaded">
		{include file="Record/ajax-description-popup.tpl"}
	</div>
{/strip}