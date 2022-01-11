{if $recordDriver}
<script type="text/javascript">
	{literal}$(document).ready(function(){{/literal}
		AspenDiscovery.GroupedWork.loadMoreLikeThis('{$recordDriver->getPermanentId()|escape:"url"}');
		AspenDiscovery.GroupedWork.loadEnrichmentInfo('{$recordDriver->getPermanentId()|escape:"url"}');
		AspenDiscovery.GroupedWork.loadReviewInfo('{$recordDriver->getPermanentId()|escape:"url"}');
        AspenDiscovery.GroupedWork.loadDescription('{$recordDriver->getPermanentId()|escape:"url"}');
		{if $enableProspectorIntegration == 1}
		AspenDiscovery.Prospector.loadRelatedProspectorTitles('{$recordDriver->getPermanentId()|escape:"url"}')
		{/if}
		{literal}});{/literal}
</script>
{/if}