{strip}
	<h1 class="hiddenTitle">{translate text="Combined Results" isPublicFacing=true}</h1>
	<div class="result-head">
		<br/>
		<div class="clearer"></div>
	</div>

	<div id="combined-results-container">
	{section name=column loop=2}
		<div id="combined-results-column-{$smarty.section.column.index}" class="hidden-tn hidden-xs hidden-sm col-md-6">
			{foreach from=$combinedResultSections item=combinedResultSection name=searchSection}
				{if ($smarty.foreach.searchSection.index%2 == $smarty.section.column.index)}
					<div class="combined-results-section combined-results-column-{$smarty.section.column.index}">
						<h2 class="combined-results-section-title">
							{translate text=$combinedResultSection->displayName|escape isPublicFacing=true isAdminEnteredData=true}
						</h2>
						<div class="combined-results-section-results" id="combined-results-section-results-{$combinedResultSection->id}">
							<img src="/images/loading.gif" alt="loading">
						</div>
					</div>
				{/if}
			{/foreach}
		</div>
	{/section}
		<div id="combined-results-all-column"></div>{* For small width views *}
	</div>
{/strip}

<script type="text/javascript">
	AspenDiscovery.Searches.combinedResultsDefinedOrder = [
		{foreach from=$combinedResultSections item=combinedResultSection}
		"#combined-results-section-results-{$combinedResultSection->id}",
		{/foreach}
	];
	function reloadCombinedResults(){ldelim}
		{foreach from=$combinedResultSections item=combinedResultSection}
			{assign var=resultsClass value=get_class($combinedResultSection)}
			AspenDiscovery.Searches.getCombinedResults('{$resultsClass}:{$combinedResultSection->id}', '{$combinedResultSection->id}', '{$combinedResultSection->source}', '{$lookfor|escape:javascript}', '{$basicSearchType}', {$combinedResultSection->numberOfResultsToShow});
		{/foreach}
	{rdelim}

	$(function(){ldelim}
		AspenDiscovery.Searches.reorderCombinedResults();
		reloadCombinedResults();

		$(window).resize(function(){ldelim}
			AspenDiscovery.Searches.reorderCombinedResults();
		{rdelim});
	{rdelim});
</script>

