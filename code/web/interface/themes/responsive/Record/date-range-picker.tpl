{strip}
	{* Reusable inline date-range picker shell (Cally web components).
	   Wired up by AspenDiscovery.DateRangePicker.init(); styled by date-range-picker.css. *}
	<div class="date-range-picker">
		<link rel="stylesheet" href="/interface/themes/responsive/css/date-range-picker.css">
		<calendar-range id="{$rangeId|default:'date-range-picker'}" months="{$months|default:1}"{if !empty($locale)} locale="{$locale}"{/if}>
			<svg slot="previous" aria-label="{translate text='Previous month' isPublicFacing=true}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path d="M15.75 19.5 8.25 12l7.5-7.5"></path></svg>
			<svg slot="next" aria-label="{translate text='Next month' isPublicFacing=true}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path d="m8.25 4.5 7.5 7.5-7.5 7.5"></path></svg>
			{section name=month loop=$months|default:1}
				<calendar-month{if $smarty.section.month.index > 0} offset="{$smarty.section.month.index}"{/if}></calendar-month>
			{/section}
		</calendar-range>
	</div>
{/strip}
