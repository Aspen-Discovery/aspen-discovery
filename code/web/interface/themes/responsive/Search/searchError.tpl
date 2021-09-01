<div id="searchError">
	<h1>{translate text="There was an error processing your search" isPublicFacing=true}</h1>
	<div>{translate text="We were unable to process your search, try making your search shorter, and removing unnecessary punctuation." isPublicFacing=true}</div>

	{if !empty($keywordResultsLink)}
	<h2>{translate text="Try a Keyword Search?" isPublicFacing=true}</h2>
	<div>
		{translate text="Your search type is not set to Keyword.  There are <strong>%1%</strong> results when searching by keyword." 1= keywordResultsCount isPublicFacing=true}
		<a class='btn btn-xs btn-primary' href="{$keywordResultsLink}">{translate text="Search by Keyword" isPublicFacing=true}</a>.
	</div>
	{/if}

	{if !empty($searchError) && !empty($searchError.error.msg)}
		<h2>{translate text="Error description" isPublicFacing=true}</h2>
		<div>
			{$searchError.error.msg}
		</div>
	{/if}

	{if !empty($solrSearchDebug)}
		<div id="solrSearchOptionsToggle" onclick="$('#solrSearchOptions').toggle()">{translate text="Show Search Options" isPublicFacing=true}</div>
		<div id="solrSearchOptions" style="display:none">
			<pre>{translate text="Search options" isPublicFacing=true} {$solrSearchDebug}</pre>
		</div>
	{/if}

	{if !empty($solrLinkDebug)}
		<div id='solrLinkToggle' onclick='$("#solrLink").toggle()'>{translate text="Show Solr Link" isPublicFacing=true}</div>
		<div id='solrLink' style='display:none'>
			<pre>{$solrLinkDebug}</pre>
		</div>
	{/if}
</div>