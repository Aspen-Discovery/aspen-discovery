{strip}
	{* This Template is the default template used by Interface.php *}
	<div id="tableOfContentsPlaceholder" style="display:none"{if $tableOfContents} class="loaded"{/if}>

	{if $tableOfContents}
		{foreach from=$tableOfContents item=note}
			<div class="row">
				<div class="col-xs-12">{$note}</div>
			</div>
		{/foreach}
		<script type="text/javascript">
			VuFind.GroupedWork.hasTableOfContentsInRecord = true;
		</script>
	{else}
		Loading Table Of Contents...
	{/if}

	</div>
	<div id="avSummaryPlaceholder"></div>
{/strip}