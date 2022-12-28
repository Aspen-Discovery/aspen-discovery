{strip}
	<div>
		{foreach from=$recordSet item=record name="recordLoop"}
			<div class="result {if ($smarty.foreach.recordLoop.iteration % 2) == 0}alt{/if} record{$smarty.foreach.recordLoop.iteration}">
				{* This is raw HTML -- do not escape it: *}
				{$record}
			</div>
			{if !empty($showExploreMoreBar) && ($smarty.foreach.recordLoop.iteration == 2 || count($recordSet) < 2)}
				<div id="explore-more-bar-placeholder"></div>
				<script type="text/javascript">
					$(document).ready(
						function () {ldelim}
							AspenDiscovery.Searches.loadExploreMoreBar('events', '{$exploreMoreSearchTerm|escape:"html"}');
						{rdelim}
					);
				</script>
			{/if}
		{/foreach}
	</div>
{/strip}

