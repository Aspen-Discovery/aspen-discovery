{strip}
	{* Navigate search results from within the full record views *}
	<div class="search-results-navigation{* text-center*}">
		<div id="previousRecordLink" class="previous">
			{if isset($previousId)}
				<a href="/{$previousType}/{$previousId|escape:"url"}?searchId={$searchId}&amp;recordIndex={$previousIndex}&amp;page={if isset($previousPage)}{$previousPage}{else}{$page}{/if}" title="{if !$previousTitle}{translate text='Previous' isPublicFacing=true inAttribute=true}{else}{$previousTitle|truncate:180:"..."|escape:'html'}{/if}">
					<i class="fas fa-caret-left fa"></i> {translate text="Previous" isPublicFacing=true}
				</a>
			{/if}
		</div>
		<div id="returnToSearch" class="return">
			{if $lastSearch}
				<a href="{$lastSearch|escape}#record{$recordDriver->getUniqueId()|escape:"url"}">{translate text="Return to Search Results" isPublicFacing=true}</a>
			{/if}
		</div>
		<div id="nextRecordLink" class="next">
			{if isset($nextId)}
				<a href="/{$nextType}/{$nextId|escape:"url"}?searchId={$searchId}&amp;recordIndex={$nextIndex}&amp;page={if isset($nextPage)}{$nextPage}{else}{$page}{/if}" title="{if !$nextTitle}{translate text='Next' isPublicFacing=true inAttribute=true}{else}{$nextTitle|truncate:180:"..."|escape:'html'}{/if}">
					{translate text="Next" isPublicFacing=true} <i class="fas fa-caret-right fa"></i>
				</a>
			{/if}
		</div>
	</div>
{/strip}