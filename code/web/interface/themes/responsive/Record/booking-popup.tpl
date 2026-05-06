{strip}
<div id="page-content" class="content">
	<form name="placeBookingForm" id="placeBookingForm" method="post" class="form">
		<input type="hidden" name="id" id="id" value="{$recordId}">
		<input type="hidden" name="itemId" id="itemId" value="{$itemId}">
		<fieldset>
			<div id="bookingError" class="pageWarning" style="display: none"></div>

			<div class="form-group">
				<label class="control-label" for="startDate">{translate text="Start Date" isPublicFacing=true}</label>
				<input type="date" name="startDate" id="startDate" class="form-control required" min="{$smarty.now|date_format:"%Y-%m-%d"}">
			</div>

			<div class="form-group">
				<label class="control-label" for="endDate">{translate text="End Date" isPublicFacing=true}</label>
				<input type="date" name="endDate" id="endDate" class="form-control required" min="{$smarty.now|date_format:"%Y-%m-%d"}">
			</div>

			{include file='Record/pickup-location-select.tpl'}

			<div class="form-group">
				<label class="control-label" for="notes">{translate text="Notes" isPublicFacing=true}</label>
				<textarea name="notes" id="notes" class="form-control" rows="3"></textarea>
			</div>
		</fieldset>
	</form>
	<div id="placingBookingMessage" class="alert alert-info" style="display: none">
		{translate text="Placing your booking, please wait." isPublicFacing=true}
	</div>
</div>
{/strip}
