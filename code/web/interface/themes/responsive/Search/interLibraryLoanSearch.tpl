{strip}
	<h2>{translate text='in_ill_name' defaultText='In %1%' 1=$interLibraryLoanName}</h2>
	<p id='illSearchResultsNote'>
		{if $consortiumName}
			{assign var='illLibrary' value=$consortiumName}
			{translate text='ill_search_results_note' defaultText="Didn't find what you need? Items not owned by %1% can be requested from other %2% libraries to be delivered to your local library for pickup." 1=$illLibrary 2=$interLibraryLoanName}
		{elseif $homeLibrary}
			{assign var='illLibrary' value=$homeLibrary}
			{translate text='ill_search_results_note' defaultText="Didn't find what you need? Items not owned by %1% can be requested from other %2% libraries to be delivered to your local library for pickup." 1=$illLibrary 2=$interLibraryLoanName}
		{else}
			{translate text='ill_search_results_note2' defaultText="Didn't find what you need? Items not owned by the library can be requested from other %1% libraries to be delivered to your local library for pickup." 1=$interLibraryLoanName}
		{/if}
	</p>
	<div id='illSearchResultsFooter'>
		<div id='moreResultsFromIllSystem'>
			<button class="btn btn-info" onclick="window.open ('{$interLibraryLoanUrl}', 'child'); return false">{translate text="Request through ILL"}</button>
		</div>
		<div class='clearfix'>&nbsp;</div>
	</div>
{/strip}