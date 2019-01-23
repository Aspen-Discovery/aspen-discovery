{strip}
	<div class="col-xs-12">
		{* Search Navigation *}
		{include file="Archive/search-results-navigation.tpl"}
		<h2>
			{$title}
			{*{$title|escape} // plb 3/8/2017 not escaping because some titles use &amp; *}
		</h2>
		<div class="row">
			<div id="main-content" class="col-xs-12 text-center">
				{if $canView}
					<div id="view-toggle" class="btn-group" role="group" data-toggle="buttons">
						{if $anonymousMasterDownload || ($loggedIn && $verifiedMasterDownload)}
						<label class="btn btn-group-small btn-default">
							<input type="radio" name="pageView" id="view-toggle-pdf" autocomplete="off" onchange="VuFind.Archive.changeActiveBookViewer('pdf', VuFind.Archive.activeBookPage);">
							View As PDF
						</label>
						{/if}
						<label class="btn btn-group-small btn-default">
							<input type="radio" name="pageView" id="view-toggle-image" autocomplete="off" onchange="VuFind.Archive.changeActiveBookViewer('image', VuFind.Archive.activeBookPage);">
							View As Image
						</label>
						<label class="btn btn-group-small btn-default">
							<input type="radio" name="pageView" id="view-toggle-transcription" autocomplete="off" onchange="VuFind.Archive.changeActiveBookViewer('transcription', VuFind.Archive.activeBookPage);">
							View Transcription
						</label>
					</div>

					<div id="view-pdf" width="100%" height="600px">
						No PDF loaded
					</div>

					<div id="view-image" style="display: none">
						<div class="large-image-wrapper">
							<div class="large-image-content">
								<div id="pika-openseadragon" class="openseadragon"></div>
							</div>
						</div>
					</div>

					<div id="view-transcription" style="display: none" width="100%" height="600px;">
						No transcription loaded
					</div>
				{else}
					{include file="Archive/noAccess.tpl"}
				{/if}
			</div>
		</div>

		<div id="download-options">
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
<script src="{$path}/js/openseadragon/openseadragon.js" ></script>
<script src="{$path}/js/openseadragon/djtilesource.js" ></script>

<script type="text/javascript">
	{if !($anonymousMasterDownload || ($loggedIn && $verifiedMasterDownload))}
	VuFind.Archive.allowPDFView = false;
	{/if}
	{assign var=pageCounter value=1}
	VuFind.Archive.pageDetails['{$page.pid}'] = {ldelim}
		pid: '{$page.pid}',
		pdf: {if $anonymousMasterDownload || ($loggedIn && $verifiedMasterDownload)}'{$page.pdf}'{else}''{/if},
		jp2: '{$page.jp2}',
		transcript: '{$page.transcript}'
	{rdelim};
	{assign var=pageCounter value=$pageCounter+1}

	$().ready(function(){ldelim}
		{if $canView}
		VuFind.Archive.changeActiveBookViewer('{$activeViewer}', '{$page.pid}')
		{/if}
		VuFind.Archive.loadExploreMore('{$pid|urlencode}');
	{rdelim});
</script>
