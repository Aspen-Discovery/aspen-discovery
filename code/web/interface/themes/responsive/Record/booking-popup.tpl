{strip}
<div id="page-content" class="content">
	<form name="placeBookingForm" id="placeBookingForm" method="post" class="form">
		<input type="hidden" name="id" id="id" value="{$recordId}">
		<fieldset>
			<div id="bookingError" class="pageWarning" style="display: none"></div>

			<div class="form-group">
				<label class="control-label" for="itemId">{translate text="Item" isPublicFacing=true}</label>
				<select name="itemId" id="itemId" class="form-control">
					{foreach from=$bookableItems item=item}
						<option value="{$item.itemId|escape}">{$item.shelfLocation|escape} &mdash; {$item.callNumber|escape}</option>
					{/foreach}
				</select>
			</div>

			{include file='Record/booking-form-fields.tpl'}
		</fieldset>
	</form>
	<div id="placingBookingMessage" class="alert alert-info" style="display: none">
		{translate text="Placing your booking, please wait." isPublicFacing=true}
	</div>
</div>
{/strip}
