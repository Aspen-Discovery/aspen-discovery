{strip}
	{*<h2>
		{$pageTitleShort}
	</h2>*}
	<div class="result-head">
		{* User's viewing mode toggle switch *}
		{*
		{include file="Union/results-displayMode-toggle.tpl"}
		*}
		<br/>
		<div class="clearer"></div>
	</div>

	<div id="combined-results-container">
	{section name=column loop=2}
		<div id="combined-results-column-{$smarty.section.column.index}" class="hidden-tn hidden-xs hidden-sm col-md-6">
			{foreach from=$combinedResultSections item=combinedResultSection name=searchSection}
				{if ($smarty.foreach.searchSection.index%2 == $smarty.section.column.index)}
					<div class="combined-results-section combined-results-column-{$smarty.section.column.index}">
						<h3 class="combined-results-section-title">
							{$combinedResultSection->displayName}
						</h3>
						<div class="combined-results-section-results" id="combined-results-section-results-{$combinedResultSection->id}">
							<img src="{$path}/images/loading.gif" alt="loading">
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
	VuFind.Searches.combinedResultsDefinedOrder = [
		{foreach from=$combinedResultSections item=combinedResultSection}
		"#combined-results-section-results-{$combinedResultSection->id}",
		{/foreach}
	];
function reloadCombinedResults(){ldelim}
	{foreach from=$combinedResultSections item=combinedResultSection}
	VuFind.Searches.getCombinedResults('{$combinedResultSection|get_class}:{$combinedResultSection->id}', '{$combinedResultSection->id}', '{$combinedResultSection->source}', '{$lookfor}', '{$basicSearchType}', {$combinedResultSection->numberOfResultsToShow});
	{/foreach}
{rdelim};

$(function(){ldelim}
		VuFind.Searches.reorderCombinedResults();
		reloadCombinedResults();

		$(window).resize(function(){ldelim}
			VuFind.Searches.reorderCombinedResults();
		{rdelim});

{rdelim});
</script>

