{if !empty($addThis)}
	<script type="text/javascript" src="https://s7.addthis.com/js/250/addthis_widget.js?pub={$addThis|escape:"url"}"></script>
{/if}
{if $recordDriver}
<script type="text/javascript">
	{literal}$(function(){{/literal}
		AspenDiscovery.GroupedWork.loadEnrichmentInfo('{$recordDriver->getPermanentId()|escape:"url"}');
		AspenDiscovery.GroupedWork.loadReviewInfo('{$recordDriver->getPermanentId()|escape:"url"}');
        AspenDiscovery.GroupedWork.loadDescription('{$recordDriver->getPermanentId()|escape:"url"}');
		{if $enableProspectorIntegration == 1}
		AspenDiscovery.Prospector.loadRelatedProspectorTitles('{$recordDriver->getPermanentId()|escape:"url"}')
		{/if}
		{literal}});{/literal}
</script>
{/if}