{strip}{if !empty($showSeries) && empty($series.allHidden)}
	<div class="result-label col-sm-4 col-xs-12">{translate text='Series' isPublicFacing=true}</div>
	<div class="result-value col-sm-8 col-xs-12">
		{assign var=summSeries value=$series}
		{assign var=seriesLimit value=$numSeriesToShowBeforeMore+1}
		{assign var=totalSeriesShown value=0}
		{include "GroupedWork/series-shared.tpl" summSeries=$summSeries seriesLimit=$seriesLimit}
	</div>
{/if}{/strip}
