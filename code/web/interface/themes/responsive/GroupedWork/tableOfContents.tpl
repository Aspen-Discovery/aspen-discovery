{strip}
	{* This Template is the default template used by RecordInterface.php *}
	<div id="tableOfContentsPlaceholder" style="display:none"{if $tableOfContents} class="loaded"{/if}>

	{if $tableOfContents}
		{foreach from=$tableOfContents item=note}
			<div class="row">
				<div class="col-xs-12">{$note}</div>
			</div>
		{/foreach}
		<script type="text/javascript">
			AspenDiscovery.GroupedWork.hasTableOfContentsInRecord = true;
		</script>
	{else}
		{translate text="Loading Table Of Contents..." isPublicFacing=true}
	{/if}

	</div>
	<div id="avSummaryPlaceholder"></div>
{/strip}