{strip}
	<div class="col-xs-12">
		{* Search Navigation *}
		{include file="Archive/search-results-navigation.tpl"}
		<h2>
			{$title}
		</h2>

		{if $canView}
			<div class="main-project-image" oncontextmenu="return false;">
				{* TODO: restrict access to original image *}
				{if $anonymousMasterDownload || ($loggedIn && $verifiedMasterDownload)}
					<a href="{$original_image}">
				{/if}
					<img src="{$large_image}" class="img-responsive" oncontextmenu="return false;">
				{if $anonymousMasterDownload || ($loggedIn && $verifiedMasterDownload)}
					</a>
				{/if}
			</div>
		{else}
			{include file="Archive/noAccess.tpl"}
		{/if}

		<div id="download-options">
			{if $canView}
				{if $anonymousLcDownload || ($loggedIn && $verifiedLcDownload)}
					<a class="btn btn-default" href="/Archive/{$pid}/DownloadLC">{translate text="Download Large Image"}</a>
				{elseif (!$loggedIn && $verifiedLcDownload)}
					<a class="btn btn-default" onclick="return AspenDiscovery.Account.followLinkIfLoggedIn(this)" href="/Archive/{$pid}/DownloadLC">{translate text="Login to Download Large Image"}</a>
				{/if}
				{if $anonymousMasterDownload || ($loggedIn && $verifiedMasterDownload)}
					<a class="btn btn-default" href="/Archive/{$pid}/DownloadOriginal">{translate text="Download Original Image"}</a>
				{elseif (!$loggedIn && $verifiedLcDownload)}
					<a class="btn btn-default" onclick="return AspenDiscovery.Account.followLinkIfLoggedIn(this)" href="/Archive/{$pid}/DownloadOriginal">{translate text="Login to Download Original Image"}</a>
				{/if}
			{/if}
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
