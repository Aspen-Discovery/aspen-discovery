{if !empty($summSeries)}
	{assign var=totalSeriesShown value=0}
	{if !empty($summSeries.fromNovelist)}
		{assign var=seriesClass value="series_from_novelist"}
	{elseif !empty($summSeries.fromSeriesIndex)}
		{assign var=seriesClass value="series_from_series_index"}
	{else}
		{assign var=seriesClass value="series_from_marc"}
	{/if}
	{if !$summSeries.hidden}
		{assign var=totalSeriesShown value=$totalSeriesShown+1}
		<a class="{$seriesClass}" href="/Series/{$summSeries.seriesId}">{$summSeries.seriesTitle}</a>{if !empty($summSeries.volume)}<strong> {translate text="volume %1%" 1=$summSeries.volume|format_float_with_min_decimals isPublicFacing=true}</strong>{/if}<br>
	{/if}
	{if !empty($summSeries.additionalSeries)}
		{foreach from=$summSeries.additionalSeries item=additional}
			{if !$additional.hidden}
				{assign var=totalSeriesShown value=$totalSeriesShown+1}
				{if $totalSeriesShown == $seriesLimit}
					<a onclick="$('#moreSeries_{$summId}').show();$('#moreSeriesLink_{$summId}').hide();" id="moreSeriesLink_{$summId}">{translate text='More Series...' isPublicFacing=true}</a>
					<div id="moreSeries_{$summId}" style="display:none">
				{/if}
				<a class="additional_series" href="/Series/{$additional.seriesId}">{$additional.seriesTitle}</a>{if !empty($additional.volume)}<strong> {translate text="volume %1%" 1=$additional.volume|format_float_with_min_decimals isPublicFacing=true}</strong>{/if}<br>
			{/if}
		{/foreach}
		{if $totalSeriesShown >= $seriesLimit}
			</div>
		{/if}
	{/if}
{/if}
