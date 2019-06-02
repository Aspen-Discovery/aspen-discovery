{if 0} {* Hide Collection Navigation *}

{strip}
	{* Navigate search results from within the full record views *}
	<div class="search-results-navigation">
		{* Collection Navigation Links*}
		{if $isFromExhibit}
			<div id="previousRecordLink" class="previous">
				{if isset($previousUrl)}
					<a href="{$previousUrl}" onclick="AspenDiscovery.Archive.setForExhibitNavigation({$previousIndex}{if $previousPage},{$previousPage}{elseif $page},{$page}{/if}{if $collectionPid},'{$collectionPid}'{/if})" title="{if !$previousTitle}{translate text='Previous'}{else}{$previousTitle|truncate:180:"..."|escape:'html'}{/if}">
						<span class="glyphicon glyphicon-chevron-left"></span> Prev
					</a>
				{/if}
			</div>
			<div id="returnToCollection" class="return">
				{if $lastCollection}
					<a href="{$lastCollection}">Return to <strong>{$collectionName}</strong> Collection</a>
				{/if}
			</div>
			<div id="nextRecordLink" class="next">
				{if isset($nextUrl)}
					<a href="{$nextUrl}" onclick="AspenDiscovery.Archive.setForExhibitNavigation({$nextIndex}{if $nextPage},{$nextPage}{elseif $page},{$page}{/if}{if $collectionPid},'{$collectionPid}'{/if})" title="{if !$nextTitle}{translate text='Next'}{else}{$nextTitle|truncate:180:"..."|escape:'html'}{/if}">
						Next <span class="glyphicon glyphicon-chevron-right"></span>
					</a>
				{/if}
			</div>
		{else}

			{* Search Navigation Links*}
			<div id="previousRecordLink" class="previous">
				{if isset($previousUrl)}
					<a href="{*{$path}/*}{$previousUrl}?searchId={$searchId}&amp;recordIndex={$previousIndex}&amp;page={if isset($previousPage)}{$previousPage}{else}{$page}{/if}" title="{if !$previousTitle}{translate text='Previous'}{else}{$previousTitle|truncate:180:"..."|escape:'html'}{/if}">
						<span class="glyphicon glyphicon-chevron-left"></span> Prev
					</a>
				{/if}
			</div>
			<div id="returnToSearch" class="return">
				{if $lastSearch}
					<a href="{$lastSearch|escape}#record{$recordDriver->getUniqueId()|escape:"url"}">{translate text="Return to Search Results"}</a>
				{/if}
			</div>
			<div id="nextRecordLink" class="next">
				{if isset($nextUrl)}
					<a href="{*{$path}/*}{$nextUrl}?searchId={$searchId}&amp;recordIndex={$nextIndex}&amp;page={if isset($nextPage)}{$nextPage}{else}{$page}{/if}" title="{if !$nextTitle}{translate text='Next'}{else}{$nextTitle|truncate:180:"..."|escape:'html'}{/if}">
						Next <span class="glyphicon glyphicon-chevron-right"></span>
					</a>
				{/if}
			</div>
		{/if}
	</div>
{/strip}
{/if}
