{strip}
	{if $loggedIn}
		{if !empty($profile->_web_note)}
			<div class="row">
				<div id="web_note" class="alert alert-info text-center col-xs-12">{$profile->_web_note}</div>
			</div>
		{/if}
		{if !empty($accountMessages)}
			{include file='systemMessages.tpl' messages=$accountMessages}
		{/if}
		{if !empty($ilsMessages)}
			{include file='ilsMessages.tpl' messages=$ilsMessages}
		{/if}

		<span class='availableHoldsNoticePlaceHolder'></span>
		<div class="bookingSectionBody">
			{if $libraryHoursMessage}
				<div class='libraryHours alert alert-success'>{$libraryHoursMessage}</div>
			{/if}

			<h1>My Scheduled Items</h1>

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
								<input type="hidden" name="withSelectedAction" value="">
								<div id="bookingsUpdateSelectedBottom" class="bookingsUpdateSelected btn-group">
									<input type="submit" class="btn btn-sm btn-warning" name="cancelSelected" value="Cancel Selected" onclick="return AspenDiscovery.Account.cancelSelectedBookings()">
									<input type="submit" class="btn btn-sm btn-danger" name="cancelAll" value="Cancel All" onclick="return AspenDiscovery.Account.cancelAllBookings()">
								</div>
							</div>
						</form>
					</div>
				{else} {* Check to see if records are available *}
					{translate text='You do not have any items scheduled.'}
				{/if}
			{/if}
		</div>
	{else} {* Check to see if user is logged in *}
		You must sign in to view this information. Click <a href="/MyAccount/Login">here</a> to sign in.
	{/if}
{/strip}