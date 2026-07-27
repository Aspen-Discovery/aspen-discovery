{strip}
	<div class="booking-date-range form-group">
		<div class="booking-date-field">
			<label class="control-label" for="start-date">{translate text="From" isPublicFacing=true}</label>
			<input type="text" id="start-date" class="form-control required" readonly value="{$startDate|default:''|format_date_locale:'medium'}">
			<input type="hidden" name="startDate" id="start-date-value" value="{$startDate|default:''}">
		</div>
		<span class="booking-date-sep" aria-hidden="true">&#8594;</span>
		<div class="booking-date-field">
			<label class="control-label" for="end-date">{translate text="To" isPublicFacing=true}</label>
			<input type="text" id="end-date" class="form-control required" readonly value="{$endDate|default:''|format_date_locale:'medium'}">
			<input type="hidden" name="endDate" id="end-date-value" value="{$endDate|default:''}">
		</div>
	</div>

	{if !empty($currentItemId)}
		<input type="hidden" id="current-item-id" value="{$currentItemId|escape}">
	{/if}

	<div id="booking-availability-loading" class="booking-availability text-muted" aria-live="polite" hidden><em>{translate text="Loading availability…" isPublicFacing=true}</em></div>

	<div id="booking-availability" class="booking-availability" data-display-locale="{$userLang->locale|default:'en-US'|replace:'_':'-'}"></div>

	{include file='Record/pickup-location-select.tpl'}

	<script> $(function () { AspenDiscovery.Record.initBookingForm(); }); </script>

	<style>
		{literal}
		.booking-date-range{
			display:flex;
			align-items:flex-end;
			gap:.75rem;
			flex-wrap:wrap;
			margin-bottom:1rem;
		}
		.booking-date-field{
			flex:1 1 140px;
			min-width:130px;
		}
		.booking-date-sep{
			padding-bottom:.45rem;
			color:#666;
			font-size:1.2em;
			line-height:2.2;
		}
		.booking-availability{
			margin:.5rem 0 1rem;
		}
		{/literal}
	</style>
{/strip}
