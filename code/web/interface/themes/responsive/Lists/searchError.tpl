<div id="searchError">
	<h1>{translate text="There was an error processing your search" isPublicFacing=true}</h1>
	<div>{translate text="We were unable to process your search, try making your search shorter, and removing unnecessary punctuation." isPublicFacing=true}</div>

	{if !empty($keywordResultsLink)}
	<h2>{translate text="Try a Keyword Search?" isPublicFacing=true}</h2>
	<div>
		Your search type is not set to Keyword.  There are <strong>{$keywordResultsCount}</strong> results in the main collection if you <a class='btn btn-xs btn-primary' href="{$keywordResultsLink}">Search by Keyword</a>.
	</div>
	{/if}

	{if !empty($searchError) && !empty($searchError.error.msg)}
		<h2>{translate text="Error description" isPublicFacing=true}</h2>
		<div>
			{$searchError.error.msg}
		</div>
	{/if}
</div>