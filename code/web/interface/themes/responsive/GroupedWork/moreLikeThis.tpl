{strip}
	{if $recordDriver}
	<div id="moreLikeThisInfo" style="display:none" class="row">
		<div class="col-sm-12">
			{assign var="scrollerName" value="MoreLikeThis"}
			{assign var="scrollerTitle" value=""}
			{assign var="wrapperId" value="morelikethis"}
			{assign var="scrollerVariable" value="morelikethisScroller"}
			{assign var="permanentId" value=$recordDriver->getPermanentId()}
			{include file='CollectionSpotlight/titleScroller.tpl'}
		</div>
	</div>
	{/if}
{/strip}