{strip}
	<div class="col-xs-12">
		{* Search Navigation *}
		{include file="Archive/search-results-navigation.tpl"}
		<h2>
			{$title}
			{*{$title|escape} // plb 3/8/2017 not escaping because some titles use &amp; *}
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
			{* {if $canView}
				{if $anonymousMasterDownload || ($loggedIn && $verifiedMasterDownload)}
					<a class="btn btn-default" href="/Archive/{$pid}/DownloadOriginal">Download Original</a>
				{elseif (!$loggedIn && $verifiedMasterDownload)}
					<a class="btn btn-default" onclick="return VuFind.Account.followLinkIfLoggedIn(this)" href="/Archive/{$pid}/DownloadOriginal">Login to Download Original</a>
				{/if}
			{/if} *}
			{if $allowRequestsForArchiveMaterials}
				<a class="btn btn-default" href="{$path}/Archive/RequestCopy?pid={$pid}">Request Copy</a>
			{/if}
			{if $showClaimAuthorship}
				<a class="btn btn-default" href="{$path}/Archive/ClaimAuthorship?pid={$pid}">Claim Authorship</a>
			{/if}
			{if $showFavorites == 1}
				<a onclick="return VuFind.Archive.showSaveToListForm(this, '{$pid|escape}');" class="btn btn-default ">{translate text='Add to favorites'}</a>
			{/if}
		</div>

		{include file="Archive/metadata.tpl"}
	</div>
{/strip}
<script type="text/javascript">
	$().ready(function(){ldelim}
		VuFind.Archive.loadExploreMore('{$pid|urlencode}');
		{rdelim});
</script>