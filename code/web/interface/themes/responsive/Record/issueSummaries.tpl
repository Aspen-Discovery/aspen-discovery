{strip}
	{* Display Issue Summaries *}
	{foreach from=$issueSummaries item=issueSummary name=summaryLoop}
		<div class='issue-summary-row'>
			{if $issueSummary.location}
				<div class='issue-summary-location'>{$issueSummary.location}</div>
			{/if}
			<div class='issue-summary-details'>
				{if $issueSummary.identity}
					<div class="row">
						<div class="col-xs-4">Identity</div>
						<div class="col-xs-8">{$issueSummary.identity}</div>
					</div>
				{/if}
				{if $issueSummary.callNumber}
					<div class="row">
						<div class="col-xs-4">Call Number</div>
						<div class="col-xs-12">{$issueSummary.callNumber}</div>
					</div>
				{/if}
				{if $issueSummary.latestReceived}
					<div class="row">
						<div class="col-xs-4">Latest Issue Received</div>
						<div class="col-xs-8">{$issueSummary.latestReceived}</div>
					</div>
				{/if}
				{if isset($issueSummary.holdingStatement) }
					<div class="row">
						<div class="col-xs-4">Holdings</div>
						<div class="col-xs-8">{$issueSummary.holdingStatement}</div>
					</div>
				{/if}
				{if $issueSummary.libHas}
						<div class="row">
							<div class="col-xs-4">Library Has</div>
							<div class="col-xs-8">{$issueSummary.libHas}</div>
						</div>
				{/if}

				{if count($issueSummary.holdings) > 0}
					<span id='showHoldings-{$smarty.foreach.summaryLoop.iteration}' class='btn btn-xs btn-info'>Show Individual Issues</span>
					<script	type="text/javascript">
						$('#showHoldings-{$smarty.foreach.summaryLoop.iteration}').click(function(){literal} { {/literal}
							if (!$('#showHoldings-{$smarty.foreach.summaryLoop.iteration}').hasClass('expanded')){literal} { {/literal}
								$('#issue-summary-holdings-{$smarty.foreach.summaryLoop.iteration}').slideDown();
								$('#showHoldings-{$smarty.foreach.summaryLoop.iteration}').html('Hide Individual Issues');
								$('#showHoldings-{$smarty.foreach.summaryLoop.iteration}').addClass('expanded');
								{literal} }else{ {/literal}
								$('#issue-summary-holdings-{$smarty.foreach.summaryLoop.iteration}').slideUp();
								$('#showHoldings-{$smarty.foreach.summaryLoop.iteration}').removeClass('expanded');
								$('#showHoldings-{$smarty.foreach.summaryLoop.iteration}').html('Show Individual Issues');
								{literal} } {/literal}
							{literal} }); {/literal}
					</script>
				{/if}
				{if $showCheckInGrid && $issueSummary.checkInGridId}
					&nbsp;
					<span id='showCheckInGrid-{$smarty.foreach.summaryLoop.iteration}' class='btn btn-xs btn-info'>Show Check-in Grid</span>
					<script	type="text/javascript">
						$('#showCheckInGrid-{$smarty.foreach.summaryLoop.iteration}').click(function(){literal} { {/literal}
							AspenDiscovery.Account.ajaxLightbox('{$path}/{$activeRecordProfileModule}/{$id}/CheckInGrid?lookfor={$issueSummary.checkInGridId}', false);
							{literal} }); {/literal}
					</script>
				{/if}
			</div>

			{if count($issueSummary.holdings) > 0}
				<div id='issue-summary-holdings-{$smarty.foreach.summaryLoop.iteration}' class='issue-summary-holdings striped' style='display:none;'>
					{include file="Record/copiesTableHeader.tpl"}
					{foreach from=$issueSummary.holdings item=holding}
						{include file="Record/copiesTableRow.tpl"}
					{/foreach}
				</div>
			{/if}
		</div>
	{/foreach}
{/strip}