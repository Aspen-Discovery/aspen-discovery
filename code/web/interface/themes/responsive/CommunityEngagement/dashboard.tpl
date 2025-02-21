{strip}
<div class="main-content">
	<h2>{translate text="Patron Engagement Report" isAdminFacing=true}</h2>
	<form method="get" action="" class="form-inline" id="campaignReportForm">
		<label for="campaign">Select Campaign:</label>
		<select name="campaign" id="campaign">
			<option value="">All Campaigns</option>
			{foreach from=$campaigns item=campaign}
				<option value="{$campaign->id}" {if $campaign->id == $selectedCampaignId}selected{/if}>{$campaign->name}</option>
			{/foreach}
		</select><br/>

		<label for="date_from">From:</label>
		<input type="date" name="date_from" id="date_from" placeholder="mm/dd/yyyy" class="form-control" size="5" value="{$selectedDateFrom|escape}">


		<label for="date_to">To:</label>
		<input type="date" name="date_to" id="date_to" placeholder="mm/dd/yyyy" class="form-control" size="5" value="{$selectedDateTo|escape}">



		<button type="submit" class="btn btn-primary" name="download_report" value="true">Download CSV</button>
	</form>
</div>
{/strip}