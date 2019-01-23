{strip}
	<div id="moreLikeThisInfo" style="display:none">
		<div class="sectionHeader">More Like This</div>
		<div class="col-sm-12">
			{assign var="scrollerName" value="MoreLikeThis"}
			{assign var="scrollerTitle" value=""}
			{assign var="wrapperId" value="morelikethis"}
			{assign var="scrollerVariable" value="morelikethisScroller"}
			{assign var="permanentId" value=$recordDriver->getPermanentId()}
			{include file='ListWidget/titleScroller.tpl'}
		</div>
	</div>
{/strip}