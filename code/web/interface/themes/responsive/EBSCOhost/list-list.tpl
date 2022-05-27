{foreach from=$recordSet item=record name="recordLoop"}
	<div class="result {if ($smarty.foreach.recordLoop.iteration % 2) == 0}alt{/if} record{$smarty.foreach.recordLoop.iteration}">
		{* This is raw HTML -- do not escape it: *}
		{$record}
	</div>
	{if !empty($researchStarters) && ($smarty.foreach.recordLoop.iteration == 2 || count($recordSet) < 2)}
		{$researchStarters}
	{/if}
	{if $showExploreMoreBar && ($smarty.foreach.recordLoop.iteration == 2 || count($recordSet) < 2)}
		<div id="explore-more-bar-placeholder"></div>
		<script type="text/javascript">
			$(document).ready(
				function () {ldelim}
					AspenDiscovery.Searches.loadExploreMoreBar('ebsco_eds', '{$exploreMoreSearchTerm|escape:"html"}');
				{rdelim}
			);
		</script>
	{/if}
{/foreach}
