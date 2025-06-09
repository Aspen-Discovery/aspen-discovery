{strip}
{*	<p>Talpa Search is a .... <a target="_blank" href="https://www.talpasearch.com/about">Click here for more information</a></p>*}
	<div id="talpaExplainerText">
		<h4>About {$talpaSearchSourceString}</h4>
		{if $includeTalpaLogoSwitch}
			<img src="https://lt-pics.s3.amazonaws.com/pics/talpa/5/talpa-logo-title_393.png" height="90px" alt="Talpa Logo">
		{/if}
		<div id="talpaExplainerTextTruncated" ">
			{$talpaExplainerText|truncate:330:"..."}
			<a onClick="talpaExplainerShowMore(); return false;">(show more)</a>
		</div>
		<div id="talpaExplainerTextFull" style="display: none; ">
			{$talpaExplainerText} <a onClick="talpaExplainerShowLess(); return false;">(show less)</a>
		</div>

	</div>

	{if $filterListApplied == 'global'}
{*	{if $recordCount || $limitList}*}
{*		<div id="refineSearch">*}
{*			*}{* Narrow Results *}
{*			<div class="row">*}
{*				{include file="Search/Recommend/limits.tpl"}*}
{*			</div>*}
{*		</div>*}
{*	{/if}*}

{*	{if $recordCount || $sideRecommendations}*}
{*		<div id="refineSearch">*}
{*			*}{* Narrow Results *}
{*			<div class="row">*}
{*				{include file="Search/Recommend/SideFacets.tpl"}*}
{*			</div>*}
{*		</div>*}
{*	{/if}*}
	{/if}
{/strip}
<script type="application/javascript">
	{literal}
	function talpaExplainerShowMore() {
		$("#talpaExplainerTextFull").show();
		$("#talpaExplainerTextTruncated").hide();
	}

	function talpaExplainerShowLess() {
		$("#talpaExplainerTextFull").hide();
		$("#talpaExplainerTextTruncated").show();
	}
	{/literal}
</script>
