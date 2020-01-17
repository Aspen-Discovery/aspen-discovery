{strip}
	<div id="scrollerTitle{$listName}{$key}" class="scrollerTitle">
		<a href="{$titleURL}" id="descriptionTrigger{$shortId}">
		<img src="{$imageUrl}" class="scrollerTitleCover" alt="{$title} Cover"/>
		</a>
		{* show ratings check in the template *}
		{include file="GroupedWork/title-rating.tpl" showNotInterested=false}
	</div>
	<div id="descriptionPlaceholder{$id}" style="display:none" class="loaded">
		{include file="Record/ajax-description-popup.tpl"}
	</div>
{/strip}