{strip}
<div id="page-content" class="content">
	<form name="placeBookingForm" id="place-booking-form" method="post" class="form">
		<input type="hidden" name="id" id="id" value="{$recordId}">
		<fieldset>
			<div id="booking-error" class="pageWarning" style="display: none"></div>

			<div class="form-group">
				<label class="control-label" for="booking-item-select">{translate text="Item" isPublicFacing=true}</label>
				<select name="itemId" id="booking-item-select" class="form-control">
					{foreach from=$bookableItems item=item}
						<option value="{$item.itemId|escape}">{$item.shelfLocation|escape} &mdash; {$item.callNumber|escape}</option>
					{/foreach}
				</select>
			</div>

			{include file='Record/booking-form-fields.tpl'}
		</fieldset>
	</form>
	<div id="placing-booking-message" class="alert alert-info" style="display: none">
		{translate text="Placing your booking, please wait." isPublicFacing=true}
	</div>
</div>
{/strip}
