<h1 class="hiddenTitle">{translate text='Talpa Search Results'}</h1>
<div id="searchInfo">
	{* Library/Other Results Facets *}
	{if !empty($topRecommendations)}
		{foreach from=$topRecommendations item="recommendations"}
			{include file=$recommendations}
		{/foreach}
	{/if}
	{if $talpa_warning}
{*		<h4 class="talpa-warning">{$talpa_warning}</h4>*}
		<p class="alert alert-warning">{$talpa_warning}</p>
	{/if}
	<div class="result-head">
		{* User's viewing mode toggle switch *}
		{if !empty($replacementTerm)}
			<div id="replacement-search-info-block">
				<div id="replacement-search-info"><span class="replacement-search-info-text">{translate text="Showing Results for" isPublicFacing=true}</span> {$replacementTerm}</div>
				<div id="original-search-info"><span class="replacement-search-info-text">{translate text="Search instead for" isPublicFacing=true} </span><a href="{$oldSearchUrl}">{$oldTerm}</a></div>
			</div>
		{/if}

		{if !empty($solrSearchDebug)}
			<div id="solrSearchOptionsToggle" onclick="$('#solrSearchOptions').toggle()">{translate text="Show Search Options" isAdminFacing=true}</div>
			<div id="solrSearchOptions" style="display:none">
				<pre>{translate text="Search options" isPublicFacing=true} {$solrSearchDebug}</pre>
			</div>
		{/if}

		{if !empty($solrLinkDebug)}
			<div id='solrLinkToggle' onclick='$("#solrLink").toggle()'>{translate text="Show Solr Link" isAdminFacing=true}</div>
			<div id='solrLink' style='display:none'>
				<pre>{$solrLinkDebug}</pre>
			</div>
		{/if}

		{if !empty($debugTiming)}
			<div id='solrTimingToggle' onclick='$("#solrTiming").toggle()'>{translate text="Show Solr Timing" isAdminFacing=true}</div>
			<div id='solrTiming' style='display:none'>
				<pre>{$debugTiming}</pre>
			</div>
		{/if}

		<div class="clearer"></div>
	</div>

<input type="hidden" id="isbn_summary_retrieval_key" value="{$uniq_key_for_summary_retrieval}" />
<input type="hidden" id="isbns_for_summary_retrieval" value="{$isbnS_for_summary_retrieval}" />
	{if !empty($subpage)}
		{include file=$subpage}
	{else}
		{$pageContent}
	{/if}

	{if !empty($pageLinks.all)}<div class="text-center">{$pageLinks.all}</div>{/if}
</div>

{* Embedded Javascript For this Page *}
<script type="text/javascript">

	$(function(){ldelim}
		if ($('#horizontal-menu-bar-container').is(':visible')) {ldelim}
			$('#home-page-search').show();  {*// Always show the searchbox for search results in mobile views.*}
		{rdelim}

		{if empty($onInternalIP)}
			{* Because content is served on the page, have to set the mode that was used, even if the user didn't choose the mode. *}
			AspenDiscovery.Searches.displayMode = '{$displayMode}';
		{else}
			AspenDiscovery.Searches.displayMode = '{$displayMode}';
			Globals.opac = 1; {* set to true to keep opac browsers from storing browse mode *}
		{/if}
		$('#'+AspenDiscovery.Searches.displayMode).parent('label').addClass('active'); {* show user which one is selected *}
		{rdelim});

	//Talpa Summaries

	function talpaSummaryShowMore(isbn) {
			$("#talpaSummaryTextFull-"+isbn).show();
			$("#talpaSummaryTextTruncated-"+isbn).hide();
		}

	function talpaSummaryShowLess(isbn){
		$("#talpaSummaryTextFull-"+isbn).hide();
		$("#talpaSummaryTextTruncated-"+isbn).show();
	}

	_summary_itemsA = $('.talpa_list_summary[data-isbn]');

	if (_summary_itemsA.length) {
		_summary_itemsA.each(function () {
			var _summary = $(this);
			var _loadingblock = $('<div class="text_loadingblock"></div>');
			var _loadingline = '<div class="talpaTextLoadingLine talpaTextLoading"></div>';
			_loadingblock.append(_loadingline + _loadingline + _loadingline  + _loadingline + _loadingline);
			_summary.empty().append(_loadingblock);
		});
	}


	var isbnA = [];
	try {
		isbnA = JSON.parse($('#isbns_for_summary_retrieval').val());
	} catch(err) {
		console.log('ERROR: parsing summary isbn value')
	}
	var isbnS = isbnA.join(',');
	var hashkey = $('#isbn_summary_retrieval_key').val();

	var summary_update = {
		url: 'https://www.librarything.com/ajax_syn_summaries.php',
		params: {
			isbns: isbnS,
			key: hashkey,
			v: 2 // to get longer summaries
		},
		isbnA: isbnA,
		elementA: {
		},
		summary_callback: function(r) {
			if (r) {
				if (typeof r === 'object') {
					_summary_itemsA.each(function() {
						var _summary = $(this);
						var _isbn = _summary.attr('data-isbn');
						if (_isbn) {
							var _data_sum = r['isbn:'+_isbn];
							if (r['isbn:'+_isbn]) {
								var _sum_item = r['isbn:'+_isbn];
								if (_sum_item.summary) {



									var summaryText = _sum_item.summary;
									var truncatedText = summaryText.length > 300 ? summaryText.substring(0, 300) + "..." : summaryText;

									var summaryHtml ='<div id="talpaSummaryTextTruncated-'+_isbn+'">';
									summaryHtml+= truncatedText;
									if(summaryText.length > 300)
										{
											summaryHtml += '<a href="#" onClick="talpaSummaryShowMore(\'' + _isbn + '\'); return false;">(show more)</a>';
										}
									summaryHtml += '</div>';
									summaryHtml += '<div id="talpaSummaryTextFull-'+_isbn+'" style="display: none;">';
									summaryHtml += summaryText;
									summaryHtml += '<a href="#" onClick="talpaSummaryShowLess(\'' + _isbn + '\'); return false;">(show less)</a>';
									summaryHtml += '</div>';

									_summary.html(summaryHtml);
								}
								else {
									_summary.empty();
								}
							}
							else {
								_summary.empty();
							}
						}
					})
				}
			}
			console.log(r);
		}
	}
	$.post(summary_update.url, summary_update.params, summary_update.summary_callback);




</script>



<script type="text/javascript">

			if ($('#refineSearchButton').is(':visible')) {ldelim}
				$('#refineSearchButton').closest('div').remove();
				var searchButtonParent = $('#horizontal-search-button-container .col-sm-12');
				searchButtonParent.removeClass('col-tn-6 col-xs-6 col-sm-12 col-md-12');

				//center the Search button
				searchButtonParent.addClass('col-xs-12 col-sm-6 col-sm-offset-3');

			{rdelim}

	</script>

