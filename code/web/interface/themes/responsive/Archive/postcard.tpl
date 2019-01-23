{* {strip} *}
	<div class="col-xs-12">
		{* Search Navigation *}
		{include file="Archive/search-results-navigation.tpl"}
		<h2>
			{$title}
			{*{$title|escape} // plb 3/8/2017 not escaping because some titles use &amp; *}
		</h2>

		{if $canView}
			<div class="large-image-wrapper">
				<div class="large-image-content" oncontextmenu="return false;">
					<div id="pika-openseadragon" class="openseadragon"></div>
				</div>
			</div>

			<div id="alternate-image-navigator">
				<img src="{$front_thumbnail}" class="img-responsive alternate-image" onclick="VuFind.Archive.openSeaDragonViewer.goToPage(0);">
				<img src="{$back_thumbnail}" class="img-responsive alternate-image" onclick="VuFind.Archive.openSeaDragonViewer.goToPage(1);">
			</div>

		{else}
			{include file="Archive/noAccess.tpl"}
		{/if}

		<div id="download-options">
			{if $canView}
				{if $anonymousLcDownload || ($loggedIn && $verifiedLcDownload)}
					<a class="btn btn-default" href="/Archive/{$pid}/DownloadLC">Download Large Image</a>
				{elseif (!$loggedIn && $verifiedLcDownload)}
					<a class="btn btn-default" onclick="return VuFind.Account.followLinkIfLoggedIn(this)" href="/Archive/{$pid}/DownloadLC">Login to Download Large Image</a>
				{/if}
				{if $anonymousMasterDownload || ($loggedIn && $verifiedMasterDownload)}
					<a class="btn btn-default" href="/Archive/{$pid}/DownloadOriginal">Download Original Image</a>
				{elseif (!$loggedIn && $verifiedLcDownload)}
					<a class="btn btn-default" onclick="return VuFind.Account.followLinkIfLoggedIn(this)" href="/Archive/{$pid}/DownloadOriginal">Login to Download Original Image</a>
				{/if}
			{/if}
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
	<script src="{$path}/js/openseadragon/openseadragon.js" ></script>
	<script src="{$path}/js/openseadragon/djtilesource.js" ></script>
	{if $canView}
	<script type="text/javascript">
		$(document).ready(function(){ldelim}
			if (!$('#pika-openseadragon').hasClass('processed')) {ldelim}
				var openSeadragonSettings = {ldelim}
					"pid":"{$pid}",
					"resourceUri":{$front_image|@json_encode nofilter},
					"tileSize":256,
					"tileOverlap":0,
					"id":"pika-openseadragon",
					"settings": VuFind.Archive.openSeadragonViewerSettings()
				{rdelim};
				openSeadragonSettings.settings.tileSources = new Array();
				var frontTile = new OpenSeadragon.DjatokaTileSource(
						Globals.url + "/AJAX/DjatokaResolver",
						'{$front_image}',
						openSeadragonSettings.settings
				);
				openSeadragonSettings.settings.tileSources.push(frontTile);
				var backTile = new OpenSeadragon.DjatokaTileSource(
						Globals.url + "/AJAX/DjatokaResolver",
						'{$back_image}',
						openSeadragonSettings.settings
				);
				openSeadragonSettings.settings.tileSources.push(backTile);

				VuFind.Archive.openSeaDragonViewer = new OpenSeadragon(openSeadragonSettings.settings);
				//VuFind.Archive.initializeOpenSeadragon(viewer);
				$('#pika-openseadragon').addClass('processed');
			{rdelim}
		{rdelim});
	</script>
{/if}
{* {/strip} *}
<script type="text/javascript">
	$().ready(function(){ldelim}
		VuFind.Archive.loadExploreMore('{$pid|urlencode}');
		{rdelim});
</script>