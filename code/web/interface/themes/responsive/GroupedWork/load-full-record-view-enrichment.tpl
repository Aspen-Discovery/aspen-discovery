{if !empty($recordDriver)}
<script type="text/javascript">
	{literal}$(document).ready(function(){{/literal}
		AspenDiscovery.GroupedWork.loadMoreLikeThis('{$recordDriver->getPermanentId()|escape:"url"}', '{$recordDriver->getPrimaryFormat()}');
		AspenDiscovery.GroupedWork.loadEnrichmentInfo('{$recordDriver->getPermanentId()|escape:"url"}');
		AspenDiscovery.GroupedWork.loadReviewInfo('{$recordDriver->getPermanentId()|escape:"url"}');
		AspenDiscovery.GroupedWork.loadDescription('{$recordDriver->getPermanentId()|escape:"url"}', '{$recordType|default:""}', '{$recordId|default:""|escape:"javascript"}');
		{if $enableInnReachIntegration == 1}
		AspenDiscovery.InterLibraryLoan.loadRelatedInnReachTitles('{$recordDriver->getPermanentId()|escape:"url"}')
		{/if}
		{if $enableShareItIntegration == 1}
		AspenDiscovery.InterLibraryLoan.loadRelatedShareItTitles('{$recordDriver->getPermanentId()|escape:"url"}')
		{/if}
		{literal}});{/literal}
</script>
{/if}
