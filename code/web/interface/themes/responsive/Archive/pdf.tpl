{strip}
	<div class="col-xs-12">
		{* Search Navigation *}
		{include file="Archive/search-results-navigation.tpl"}
		<h2>
			{$title}
			{*{$title|escape} // plb 3/8/2017 not escaping because some titles use &amp; *}
		</h2>
		<div class="row">
			<div id="main-content" class="col-xs-12 hidden-tn hidden-xs text-center">
				{if $canView}
					<div id="pdfContainer">
						<div id="pdfContainerBody">
							<div id="pdfComponentBox">
								<object type="pdf" data="{$pdf}" id="view-pdf" class="book-pdf">
									<embed type="application/pdf" src="{$pdf}" class="book-pdf">
								</object>
							</div>
						</div>
					</div>
				{else}
					{include file="Archive/noAccess.tpl"}
				{/if}
			</div>
		</div>

		<div id="download-options" class="row">
			<div class="col-xs-12">
				{if $canView}
					<a class="btn btn-default" href="/Archive/{$pid}/DownloadPDF">Download PDF</a>
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
		</div>

		{include file="Archive/metadata.tpl"}
	</div>
{/strip}
<script type="text/javascript">
	$().ready(function(){ldelim}
		VuFind.Archive.loadExploreMore('{$pid|urlencode}');
		{rdelim});
</script>