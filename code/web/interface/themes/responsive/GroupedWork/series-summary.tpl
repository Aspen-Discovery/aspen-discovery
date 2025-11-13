{strip}{if !empty($showSeries) && empty($series.allHidden)}
	<div class="result-label col-sm-4 col-xs-12">{translate text='Series' isPublicFacing=true}</div>
	<div class="result-value col-sm-8 col-xs-12">
		{assign var=summSeries value=$series}
		{if !empty($summSeries.fromNovelist)}
			<a href="/GroupedWork/{$recordDriver->getPermanentId()}/Series">{$summSeries.seriesTitle}</a>{if !empty($summSeries.volume)}<strong> {translate text="volume %1%" 1=$summSeries.volume|format_float_with_min_decimals isPublicFacing=true}</strong>{/if}
		{elseif !empty($summSeries.fromSeriesIndex)}
			{if !$summSeries.hidden}
				<a href="/Series/{$summSeries.seriesId}">{$summSeries.seriesTitle}</a>{if !empty($summSeries.volume)}<strong> {translate text="volume %1%" 1=$summSeries.volume|format_float_with_min_decimals isPublicFacing=true}</strong>{/if}<br>
			{/if}
			{if !empty($summSeries.additionalSeries)}
				{assign var=numSeriesShown value=1}
				{foreach from=$summSeries.additionalSeries item=additional}
					{if !$additional.hidden}
						{assign var=numSeriesShown value=$numSeriesShown+1}
						{if $numSeriesShown == 4}
							<a onclick="$('#moreSeries').show();$('#moreSeriesLink').hide();" id="moreSeriesLink">{translate text='More Series...' isPublicFacing=true}</a>
							<div id="moreSeries" style="display:none">
						{/if}
						<a href="/Series/{$additional.seriesId}">{$additional.seriesTitle}</a>{if !empty($additional.volume)}<strong> {translate text="volume %1%" 1=$additional.volume|format_float_with_min_decimals isPublicFacing=true}</strong>{/if}<br>
					{/if}
				{/foreach}
				{if $numSeriesShown >= 4}
					</div>
				{/if}
			{/if}
		{elseif !empty($summSeries.seriesTitle)}
			<a href="/Search/Results?searchIndex=Series&lookfor={$summSeries.seriesTitle}&sort=year+asc%2Ctitle+asc">{$summSeries.seriesTitle}</a>{if !empty($summSeries.volume)}<strong> {translate text="volume %1%" 1=$summSeries.volume|format_float_with_min_decimals isPublicFacing=true}</strong>{/if}<br>
			{if !empty($summSeries.additionalSeries)}
				{assign var=numSeriesShown value=1}
				{foreach from=$summSeries.additionalSeries item=additional}
					{assign var=numSeriesShown value=$numSeriesShown+1}
					{if $numSeriesShown == 4}
						<a onclick="$('#moreSeries_{$recordDriver->getPermanentId()}').show();$('#moreSeriesLink_{$recordDriver->getPermanentId()}').hide();" id="moreSeriesLink_{$recordDriver->getPermanentId()}">{translate text='More Series...' isPublicFacing=true}</a>
						<div id="moreSeries_{$recordDriver->getPermanentId()}" style="display:none">
					{/if}
					<a href="/Search/Results?searchIndex=Series&lookfor={$additional.seriesTitle}&sort=year+asc%2Ctitle+asc">{$additional.seriesTitle}</a>{if !empty($additional.volume)}<strong> {translate text="volume %1%" 1=$additional.volume|format_float_with_min_decimals isPublicFacing=true}</strong>{/if}<br>
				{/foreach}
				{if $numSeriesShown >= 4}
					</div>
				{/if}
			{/if}
		{/if}
	</div>
{/if}{/strip}
