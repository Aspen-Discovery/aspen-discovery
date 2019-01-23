{strip}
	{if $loggedIn}
		{if $profile->web_note}
			<div class="row">
				<div id="web_note" class="alert alert-info text-center col-xs-12">{$profile->web_note}</div>
			</div>
		{/if}

		{* Alternate Mobile MyAccount Menu *}
		{include file="MyAccount/mobilePageHeader.tpl"}

		<span class='availableHoldsNoticePlaceHolder'></span>

		<div class="bookingSectionBody">
			{if $libraryHoursMessage}
				<div class='libraryHours alert alert-success'>{$libraryHoursMessage}</div>
			{/if}

				<h3>My Scheduled Items</h3>

	{if $offline}
		<div class="alert alert-warning"><strong>The library system is currently offline.</strong> We are unable to retrieve information about your scheduled items at this time.</div>
	{else}

		<p class="alert alert-info">
			{translate text="booking summary"}
		</p>

			{if $recordList}
					<div class="striped">
						{foreach from=$recordList item=record name="recordLoop"}
								{include file="MyAccount/bookedItem.tpl" record=$record resultIndex=$smarty.foreach.recordLoop.iteration}
						{/foreach}
					</div>

					{* Code to handle updating multiple bookings at one time *}
					<br>
					<div class="bookingsWithSelected">
						<form id="withSelectedHoldsFormBottom"{* action="{$fullPath}"*}>{*TODO: no action set.*}
							<div>
								<input type="hidden" name="withSelectedAction" value="" >
								<div id="bookingsUpdateSelectedBottom" class="bookingsUpdateSelected btn-group">
									<input type="submit" class="btn btn-sm btn-warning" name="cancelSelected" value="Cancel Selected" onclick="return VuFind.Account.cancelSelectedBookings()">
									<input type="submit" class="btn btn-sm btn-danger" name="cancelAll" value="Cancel All" onclick="return VuFind.Account.cancelAllBookings()">
									{*<input type="submit" class="btn btn-sm btn-default" id="exportToExcel{if $sectionKey=='available'}Available{else}Unavailable{/if}Bottom" name="exportToExcel{if $sectionKey=='available'}Available{else}Unavailable{/if}" value="Export to Excel" />*}
								</div>
							</div>
						</form>
					</div>
				{else} {* Check to see if records are available *}
						{translate text='You do not have any items scheduled.'}
			{/if}
	{/if}
		</div>
		{* TODO: sorting Bookings listings *}
{*		<script type="text/javascript">
			$(document).ready(function() {literal} { {/literal}
				$("#holdsTableavailable").tablesorter({literal}{cssAsc: 'sortAscHeader', cssDesc: 'sortDescHeader', cssHeader: 'unsortedHeader', headers: { 0: { sorter: false}, 3: {sorter : 'date'}, 4: {sorter : 'date'}, 7: { sorter: false} } }{/literal});
				{literal} }); {/literal}
		</script>*}
	{else} {* Check to see if user is logged in *}
		You must login to view this information. Click <a href="{$path}/MyAccount/Login">here</a> to login.
	{/if}
{/strip}