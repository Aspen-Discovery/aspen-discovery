{strip}
	{if !empty($updateResults)}
		<div class="contents">
			{if !empty($updateResults.success)}
				<div class="alert alert-success">{$updateResults.message}</div>
			{else}
				<div class="alert alert-danger">{$updateResults.message}</div>
			{/if}
		</div>
	{else}
		<div id="page-content" class="content">
			<form name="updateBookingForm" id="update-booking-form" method="post" class="form">
				<input type="hidden" name="userId" id="user-id" value="{$userId|escape}">
				<input type="hidden" name="bookingId" id="booking-id" value="{$bookingId|escape}">
				<fieldset>
					<div id="booking-error" class="pageWarning" style="display: none"></div>
	
					<div class="form-group">
						<label class="control-label" for="booking-item-label">{translate text="Item" isPublicFacing=true}</label>
						<input type="text" id="booking-item-label" class="form-control" value="{$itemLabel|escape}" disabled>
					</div>
					{include file='Record/booking-form-fields.tpl' startDate=$startDate endDate=$endDate notes=$notes}
				</fieldset>
			</form>
		</div>
	{/if}
{/strip}
