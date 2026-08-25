{strip}
	{if !empty($currentItemId)}
		<input type="hidden" id="current-item-id" value="{$currentItemId|escape}">
	{/if}

	<div id="booking-availability-loading" class="booking-availability text-muted" aria-live="polite" hidden><em>{translate text="Loading availability…" isPublicFacing=true}</em></div>

	{assign var="bookingCalendarLocale" value=$userLang->locale|default:'en-US'|replace:'_':'-'}
	{include file="Record/date-range-picker.tpl" rangeId="booking-calendar" startName="startDate" endName="endDate" startValue=$startDate endValue=$endDate months=1 locale=$bookingCalendarLocale}

	{include file='Record/pickup-location-select.tpl'}

	<script> $(function () { AspenDiscovery.Record.initBookingForm(); }); </script>

	<style>
		{literal}
		.booking-availability{
			margin:.5rem 0 1rem;
		}
		{/literal}
	</style>
{/strip}
