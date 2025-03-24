{strip}{if !empty($showSeries)}
	<div class="result-label col-sm-4 col-xs-12">{translate text='Series' isPublicFacing=true}</div>
	<div class="result-value col-sm-8 col-xs-12">
		{assign var=summSeries value=$series}
		{if !empty($summSeries.fromNovelist)}
			<a href="/GroupedWork/{$recordDriver->getPermanentId()}/Series">{$summSeries.seriesTitle}</a>{if !empty($summSeries.volume)}<strong> {translate text="volume %1%" 1=$summSeries.volume|format_float_with_min_decimals isPublicFacing=true}</strong>{/if}
		{elseif !empty($summSeries.fromSeriesIndex)}
			<a href="/Series/{$summSeries.seriesId}">{$summSeries.seriesTitle}</a>{if !empty($summSeries.volume)}<strong> {translate text="volume %1%" 1=$summSeries.volume|format_float_with_min_decimals isPublicFacing=true}</strong>{/if}
		{else}
			<a href="/Search/Results?searchIndex=Series&lookfor={$summSeries.seriesTitle}&sort=year+asc%2Ctitle+asc">{$summSeries.seriesTitle}</a>{if !empty($summSeries.volume)}<strong> {translate text="volume %1%" 1=$summSeries.volume|format_float_with_min_decimals isPublicFacing=true}</strong>{/if}
		{/if}
		{if !empty($indexedSeries)}
			{if !empty($summSeries)}
				<br/>
			{/if}
			{assign var=numSeriesShown value=0}
			{foreach from=$indexedSeries item=seriesItem name=loop}
				{if !isset($series.seriesTitle) || ((strpos(strtolower($seriesItem.seriesTitle), strtolower($series.seriesTitle)) === false) && (strpos(strtolower($series.seriesTitle), strtolower($seriesItem.seriesTitle)) === false))}
					{assign var=numSeriesShown value=$numSeriesShown+1}
					{if $numSeriesShown == 4}
						<a onclick="$('#moreSeries_{$recordDriver->getPermanentId()}').show();$('#moreSeriesLink_{$recordDriver->getPermanentId()}').hide();" id="moreSeriesLink_{$recordDriver->getPermanentId()}">{translate text="More Series..." isPublicFacing=true}</a>
						<div id="moreSeries_{$recordDriver->getPermanentId()}" style="display:none">
					{/if}
					<a href="/Search/Results?searchIndex=Series&lookfor=%22{$seriesItem.seriesTitle|removeTrailingPunctuation|escape:"url"}%22&sort=year+asc%2Ctitle+asc">{$seriesItem.seriesTitle|removeTrailingPunctuation|escape}</a>{if !empty($seriesItem.volume)}<strong> {translate text="volume %1%" 1=$seriesItem.volume|format_float_with_min_decimals isPublicFacing=true}</strong>{/if}<br/>
				{/if}
			{/foreach}
			{if $numSeriesShown >= 4}
				</div>
			{/if}
		{/if}
	</div>
{/if}{/strip}
