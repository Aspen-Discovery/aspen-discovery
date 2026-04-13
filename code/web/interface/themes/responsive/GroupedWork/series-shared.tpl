{if !empty($summSeries)}
	{assign var=totalSeriesShown value=0}
	{assign var=additionalSeriesLink value="/Search/Results?lookfor={$summSeries.seriesTitle|escape:url}&searchIndex=Series"}
	{if !empty($summSeries.fromNovelist)}
		{assign var=seriesClass value="series_from_novelist"}
		{assign var=seriesLink value="/GroupedWork/{$summSeries.groupedWorkId}/Series"}
	{elseif !empty($summSeries.fromSeriesIndex)}
		{assign var=seriesClass value="series_from_series_index"}
		{assign var=seriesLink value="/Series/{$summSeries.seriesId}"}
		{assign var=additionalSeriesLink value="/Series/{$summSeries.seriesId}"}
	{else}
		{assign var=seriesClass value="series_from_marc"}
		{assign var=seriesLink value="/Search/Results?lookfor={$summSeries.seriesTitle|escape:url}&searchIndex=Series"}
	{/if}
	{if empty($summSeries.hidden)}
		{assign var=totalSeriesShown value=$totalSeriesShown+1}
		<a class="{$seriesClass}" href="{$seriesLink}">{$summSeries.seriesTitle}</a>{if !empty($summSeries.volume)}<strong> {translate text="volume %1%" 1=$summSeries.volume|format_float_with_min_decimals isPublicFacing=true}</strong>{/if}<br>
	{/if}
	{if !empty($summSeries.additionalSeries)}
		{foreach from=$summSeries.additionalSeries item=additional}
			{if empty($additional.hidden)}
				{assign var=totalSeriesShown value=$totalSeriesShown+1}
				{if $totalSeriesShown == $seriesLimit}
					<a onclick="$('#moreSeries_{$summId}').show();$('#moreSeriesLink_{$summId}').hide();" id="moreSeriesLink_{$summId}">{translate text='More Series...' isPublicFacing=true}</a>
					<div id="moreSeries_{$summId}" style="display:none">
				{/if}
				<a class="additional_series" href="{$additionalSeriesLink}">{$additional.seriesTitle}</a>{if !empty($additional.volume)}<strong> {translate text="volume %1%" 1=$additional.volume|format_float_with_min_decimals isPublicFacing=true}</strong>{/if}<br>
			{/if}
		{/foreach}
		{if $totalSeriesShown >= $seriesLimit}
			</div>
		{/if}
	{/if}
{/if}
