<div id="searchError">
	<h1>{translate text="There was an error processing your search"}</h1>
	<div>We were unable to process your search, try making your search shorter, and removing unnecessary punctuation.</div>

	{if !empty($keywordResultsLink)}
	<h2>{translate text="Try a Keyword Search?"}</h2>
	<div>
		Your search type is not set to Keyword.  There are <strong>{$keywordResultsCount}</strong> results if you <a class='btn btn-xs btn-primary' href="{$keywordResultsLink}">Search by Keyword</a>.
	</div>
	{/if}

	{if !empty($searchError) && !empty($searchError.error.msg)}
		<h2>{translate text="Error description"}</h2>
		<div>
			{$searchError.error.msg}
		</div>
	{/if}

	{if !empty($solrSearchDebug)}
		<div id="solrSearchOptionsToggle" onclick="$('#solrSearchOptions').toggle()">Show Search Options</div>
		<div id="solrSearchOptions" style="display:none">
			<pre>Search options: {$solrSearchDebug}</pre>
		</div>
	{/if}

	{if !empty($solrLinkDebug)}
		<div id='solrLinkToggle' onclick='$("#solrLink").toggle()'>Show Solr Link</div>
		<div id='solrLink' style='display:none'>
			<pre>{$solrLinkDebug}</pre>
		</div>
	{/if}
</div>