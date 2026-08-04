{strip}
	<div id="scrollerTitle{$scrollerName}{$index}" class="scrollerTitle" onclick="return AspenDiscovery.GroupedWork.showGroupedWorkInfo('{$id}','','{if !empty($format)}{$format}{/if}', 'getMoreLikeThis');" onkeypress="return AspenDiscovery.GroupedWork.showGroupedWorkInfo('{$id}','','{if !empty($format)}{$format}{/if}', 'getMoreLikeThis')" tabindex="0">
		<img src="{$bookCoverUrlMedium}" class="scrollerTitleCover" alt="{$title} Cover"/>
	</div>
{/strip}
