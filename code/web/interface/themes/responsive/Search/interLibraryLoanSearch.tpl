{strip}
	<h2>{translate text='In %1%' 1=$interLibraryLoanName isPublicFacing=true}</h2>
	<p id='illSearchResultsNote'>
		{if !empty($consortiumName)}
			{assign var='illLibrary' value=$consortiumName}
			{translate text="Didn't find what you need? Items not owned by %1% can be requested from other %2% libraries to be delivered to your local library for pickup." 1=$illLibrary 2=$interLibraryLoanName isPublicFacing=true}
		{elseif $homeLibrary}
			{assign var='illLibrary' value=$homeLibrary}
			{translate text="Didn't find what you need? Items not owned by %1% can be requested from other %2% libraries to be delivered to your local library for pickup." 1=$illLibrary 2=$interLibraryLoanName isPublicFacing=true}
		{else}
			{translate text="Didn't find what you need? Items not owned by the library can be requested from other %1% libraries to be delivered to your local library for pickup." 1=$interLibraryLoanName isPublicFacing=true}
		{/if}
	</p>
	<div id='illSearchResultsFooter'>
		<div id='moreResultsFromIllSystem'>
			<button class="btn btn-info" onclick="window.open ('{$interLibraryLoanUrl}', 'child'); return false">{translate text="Request through ILL" isPublicFacing=true}</button>
		</div>
		<div class='clearfix'>&nbsp;</div>
	</div>
{/strip}