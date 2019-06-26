{strip}
	<div class="col-xs-12">
		{* Search Navigation *}
		{include file="Archive/search-results-navigation.tpl"}
		<h2>
			{$title}
		</h2>

		{if $canView}
			<img src="{$medium_image}" class="img-responsive">
			<audio width="100%" controls id="player" class="copy-prevention" oncontextmenu="return false;">
				<source src="{$audioLink}" type="audio/mpeg">
			</audio>

		{else}
			{include file="Archive/noAccess.tpl"}
		{/if}
		<div id="download-options">
			{if $allowRequestsForArchiveMaterials}
				<a class="btn btn-default" href="{$path}/Archive/RequestCopy?pid={$pid}">{translate text="Request Copy"}</a>
			{/if}
			{if $showClaimAuthorship}
				<a class="btn btn-default" href="{$path}/Archive/ClaimAuthorship?pid={$pid}">{translate text="Claim Authorship"}</a>
			{/if}
			{if $showFavorites == 1}
				<a onclick="return AspenDiscovery.Archive.showSaveToListForm(this, '{$pid|escape}');" class="btn btn-default ">{translate text='Add to favorites'}</a>
			{/if}
		</div>

		{include file="Archive/metadata.tpl"}
	</div>
{/strip}
<script type="text/javascript">
	$().ready(function(){ldelim}
		AspenDiscovery.Archive.loadExploreMore('{$pid|urlencode}');
		{rdelim});
</script>