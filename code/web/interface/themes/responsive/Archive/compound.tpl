{strip}
	<div class="col-xs-12">
		{* Search Navigation *}
		{include file="Archive/search-results-navigation.tpl"}
		<h1>
			{$title}
		</h1>
		<div class="row">
			<div id="main-content" class="col-xs-12 text-center">
				{if $canView}
					<div id="view-toggle" class="btn-group" role="group" data-toggle="buttons">
						{if $anonymousOriginalDownload || ($loggedIn && $verifiedOriginalDownload)}
						<label class="btn btn-group-small btn-default">
							<input type="radio" name="pageView" id="view-toggle-pdf" autocomplete="off" onchange="return AspenDiscovery.Archive.handleBookClick('{$pid}', AspenDiscovery.Archive.activeBookPage, 'pdf');">
							{*TODO: set bookPID*}

							{translate text="View As PDF"}
						</label>
						{/if}
						<label class="btn btn-group-small btn-default">
							<input type="radio" name="pageView" id="view-toggle-image" autocomplete="off" onchange="return AspenDiscovery.Archive.handleBookClick('{$pid}', AspenDiscovery.Archive.activeBookPage, 'image');">

							{translate text="View As Image"}
						</label>
						<label class="btn btn-group-small btn-default">
							<input type="radio" name="pageView" id="view-toggle-transcription" autocomplete="off" onchange="return AspenDiscovery.Archive.handleBookClick('{$pid}', AspenDiscovery.Archive.activeBookPage, 'transcription');">

							{translate text="View Transcription"}
						</label>
						<label class="btn btn-group-small btn-default">
							<input type="radio" name="pageView" id="view-toggle-audio" autocomplete="off" onchange="return AspenDiscovery.Archive.handleBookClick('{$pid}', AspenDiscovery.Archive.activeBookPage, 'audio');">

							{translate text="Listen to Audio"}
						</label>
						<label class="btn btn-group-small btn-default">
							<input type="radio" name="pageView" id="view-toggle-video" autocomplete="off" onchange="return AspenDiscovery.Archive.handleBookClick('{$pid}', AspenDiscovery.Archive.activeBookPage, 'video');">

							{translate text="Watch Video"}
						</label>
					</div>

					<br>

					<div id="view-pdf" width="100%" height="600px" style="display: none">
						{translate text="No PDF loaded"}
					</div>

					<div id="view-image" style="display: none">
						<div class="large-image-wrapper">
							<div class="large-image-content" oncontextmenu="return false;">
								<div id="custom-openseadragon" class="openseadragon"></div>
							</div>
						</div>
					</div>

					<div id="view-transcription" style="display: none" width="100%" height="600px;">
						{translate text="No transcription loaded"}
					</div>

					<div id="view-audio" style="display: none">
						<img src="" class="img-responsive">
						<audio width="100%" controls id="audio-player" oncontextmenu="return false;">
							<source src="" type="audio/mpeg" id="audio-player-src">
						</audio>
					</div>

					<div id="view-video" style="display: none">
						<video width="100%" controls poster="" id="video-player" oncontextmenu="return false;">
							<source src="" type="video/mp4" id="video-player-src">
						</video>
					</div>

				{else}
					{include file="Archive/noAccess.tpl"}
				{/if}
			</div>
		</div>

		<div id="download-options">
			{*
			<a class="btn btn-default" href="/Archive/{$pid}/DownloadPDF">Download Book As PDF</a>
			<a class="btn btn-default" href="/Archive/{$activePage}/DownloadPDF" id="downloadPageAsPDF">Download Page As PDF</a>
			*}
			<br/>
			{if $hasPdf && ($anonymousOriginalDownload || ($loggedIn && $verifiedOriginalDownload))}
				<a class="btn btn-default" href="/Archive/{$pid}/DownloadPDF">{translate text="Download PDF"}</a>
			{elseif ($hasPdf && !$loggedIn && $verifiedOriginalDownload)}
				<a class="btn btn-default" onclick="return AspenDiscovery.Account.followLinkIfLoggedIn(this)" href="/Archive/{$pid}/DownloadPDF">{translate text="Sign in to Download PDF"}</a>
			{/if}
			{if $allowRequestsForArchiveMaterials}
				<a class="btn btn-default" href="/Archive/RequestCopy?pid={$pid}">{translate text="Request Copy"}</a>
			{/if}
			{if $showClaimAuthorship}
				<a class="btn btn-default" href="/Archive/ClaimAuthorship?pid={$pid}">{translate text="Claim Authorship"}</a>
			{/if}
			{if $showFavorites == 1}
				<a onclick="return AspenDiscovery.Account.showSaveToListForm(this, 'Islandora', '{$pid|escape}');" class="btn btn-default ">{translate text='Add to list'}</a>
			{/if}
		</div>

		{if $canView}
			<div class="row">
				<div class="col-xs-12 text-center">
					<div class="jcarousel-wrapper" id="book-sections">
						<a href="#" class="jcarousel-control-prev"{* data-target="-=1"*}><i class="glyphicon glyphicon-chevron-left"></i></a>
						<a href="#" class="jcarousel-control-next"{* data-target="+=1"*}><i class="glyphicon glyphicon-chevron-right"></i></a>

						<div class="relatedTitlesContainer jcarousel"> {* relatedTitlesContainer used in initCarousels *}
							<ul>
								{assign var=pageCounter value=1}
								{foreach from=$bookContents item=section}
									{if count($section.pages) == 0}
										<li class="relatedTitle">
											<a href="{$section.link}?pagePid={$section.pid}" onclick="return AspenDiscovery.Archive.handleBookClick('{$pid}', '{$section.pid}', AspenDiscovery.Archive.activeBookViewer);">
												<figure class="thumbnail">
													<img src="{$section.cover}" alt="{$section.title|removeTrailingPunctuation|truncate:80:"..."}">
													<figcaption>{$section.title|removeTrailingPunctuation|truncate:80:"..."}</figcaption>
												</figure>
											</a>
										</li>
										{assign var=pageCounter value=$pageCounter+1}
									{else}
										{foreach from=$section.pages item=page}
											<li class="relatedTitle">
												<a href="{$page.link}?pagePid={$page.pid}" onclick="return AspenDiscovery.Archive.handleBookClick('{$pid}', '{$page.pid}', AspenDiscovery.Archive.activeBookViewer);">
													<figure class="thumbnail">
														<img src="{$page.cover}" alt="Page {$pageCounter}">
														<figcaption>{$pageCounter}</figcaption>
													</figure>
												</a>
											</li>
											{assign var=pageCounter value=$pageCounter+1}
										{/foreach}
									{/if}
								{/foreach}
							</ul>
						</div>
					</div>
				</div>
			</div>
		{/if}

		{include file="Archive/metadata.tpl"}
	</div>
{/strip}
<script src="/js/openseadragon/openseadragon.js" ></script>
<script src="/js/openseadragon/djtilesource.js" ></script>
{if $canView}
<script type="text/javascript">
	{if !($anonymousOriginalDownload || ($loggedIn && $verifiedOriginalDownload))}
		AspenDiscovery.Archive.allowPDFView = false;
	{/if}
	{assign var=pageCounter value=1}
	{foreach from=$bookContents item=section}
		AspenDiscovery.Archive.pageDetails['{$section.pid}'] = {ldelim}
			pid: '{$section.pid}',
			title: "{$section.title|escape:javascript}",
			pdf: {if $anonymousOriginalDownload || ($loggedIn && $verifiedOriginalDownload)}'{$section.pdf}'{else}''{/if},
			jp2: '',
			video: '{$section.video}',
			audio: '{$section.audio}',
			cover: '{$section.cover}',
			transcript: '{$section.transcript}',
		{rdelim};

		{foreach from=$section.pages item=page}
			AspenDiscovery.Archive.pageDetails['{$page.pid}'] = {ldelim}
				pid: '{$page.pid}',
				title: 'Page {$pageCounter}',
				pdf: {if $anonymousOriginalDownload || ($loggedIn && $verifiedOriginalDownload)}'{$page.pdf}'{else}''{/if},
				jp2: '{$page.jp2}',
				transcript: '{$page.transcript}',
				video: '{$page.video}',
				audio: '{$page.audio}',
				index: '{$pageCounter}'
			{rdelim};
			{if $page.pid == $activeObj}
				{assign var=scrollToPage value=$pageCounter}
				AspenDiscovery.Archive.curPage = {$pageCounter};
			{/if}
			{assign var=pageCounter value=$pageCounter+1}
		{foreachelse}
			{* Increment page counter once for the new section even if it's empty*}
			{assign var=pageCounter value=$pageCounter+1}
		{/foreach}
	{/foreach}

	{* Greater than 2 because we increment page counter after an object is displayed *}
	{if $pageCounter > 2}
		AspenDiscovery.Archive.multiPage = true;
	{/if}

	$(function(){ldelim}
		{* Below click events trigger indirectly the handleBookClick function, and properly sets the appropriate button. *}
		AspenDiscovery.Archive.handleBookClick('{$pid}', '{$activePage}', '{$activeViewer}');

	{rdelim});
</script>
{/if}
<script type="text/javascript">
	$().ready(function(){ldelim}
		AspenDiscovery.Archive.loadExploreMore('{$pid|urlencode}');
	{rdelim});
</script>