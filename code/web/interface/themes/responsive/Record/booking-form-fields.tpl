{strip}
	<div class="booking-date-range form-group">
		<div class="booking-date-field">
			<label class="control-label" for="startDate">{translate text="From" isPublicFacing=true}</label>
			<input type="text" name="startDate" id="startDate" class="form-control required" readonly value="{$startDate|default:''}">
		</div>
		<span class="booking-date-sep" aria-hidden="true">&#8594;</span>
		<div class="booking-date-field">
			<label class="control-label" for="endDate">{translate text="To" isPublicFacing=true}</label>
			<input type="text" name="endDate" id="endDate" class="form-control required" readonly value="{$endDate|default:''}">
		</div>
	</div>

	{if !empty($currentItemId)}
		<input type="hidden" id="currentItemId" value="{$currentItemId|escape}">
	{/if}

	<div id="bookingAvailability" class="booking-availability" aria-live="polite"></div>

	{include file='Record/pickup-location-select.tpl'}

	<div class="form-group">
		<label class="control-label" for="notes">{translate text="Notes" isPublicFacing=true}</label>
		<textarea name="notes" id="notes" class="form-control" rows="3">{$notes|default:''|escape}</textarea>
	</div>

	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
	<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
	<script> $(function () { AspenDiscovery.Record.initBookingForm(); }); </script>

	<style>
		{literal}
		.booking-date-range{display:flex;align-items:flex-end;gap:.75rem;flex-wrap:wrap;margin-bottom:1rem}
		.booking-date-field{flex:1 1 140px;min-width:130px}
		.booking-date-sep{padding-bottom:.45rem;color:#666;font-size:1.2em;line-height:2.2}
		.booking-availability{margin:.5rem 0 1rem}
		.booking-availability .flatpickr-input{display:none!important}
		.booking-availability .flatpickr-calendar{width:100%!important;box-shadow:none;border:1px solid #ddd;border-radius:4px}
		.booking-availability .flatpickr-innerContainer{justify-content:center}
		.booking-availability .flatpickr-day.flatpickr-disabled{background:#ffe8cc!important;color:#b35a00!important;text-decoration:line-through;border-color:transparent}
		.booking-availability .flatpickr-day.flatpickr-disabled:hover{background:#ffe8cc!important}
		{/literal}
	</style>
{/strip}
