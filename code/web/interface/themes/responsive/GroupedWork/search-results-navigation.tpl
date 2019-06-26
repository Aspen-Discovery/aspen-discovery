{strip}
	{* Navigate search results from within the full record views *}
	<div class="search-results-navigation{* text-center*}">
		<div id="previousRecordLink" class="previous">
			{if isset($previousId)}
				<a href="{$path}/{$previousType}/{$previousId|escape:"url"}?searchId={$searchId}&amp;recordIndex={$previousIndex}&amp;page={if isset($previousPage)}{$previousPage}{else}{$page}{/if}" title="{if !$previousTitle}{translate text='Previous'}{else}{$previousTitle|truncate:180:"..."|escape:'html'}{/if}">
					<span class="glyphicon glyphicon-chevron-left"></span> {translate text="Previous"}
				</a>
			{/if}
		</div>
		<div id="returnToSearch" class="return">
			{if $lastSearch}
				<a href="{$lastSearch|escape}#record{$recordDriver->getUniqueId()|escape:"url"}">{translate text="Return to Search Results"}</a>
			{/if}
		</div>
		<div id="nextRecordLink" class="next">
			{if isset($nextId)}
				<a href="{$path}/{$nextType}/{$nextId|escape:"url"}?searchId={$searchId}&amp;recordIndex={$nextIndex}&amp;page={if isset($nextPage)}{$nextPage}{else}{$page}{/if}" title="{if !$nextTitle}{translate text='Next'}{else}{$nextTitle|truncate:180:"..."|escape:'html'}{/if}">
					{translate text="Next"} <span class="glyphicon glyphicon-chevron-right"></span>
				</a>
			{/if}
		</div>
	</div>
{/strip}