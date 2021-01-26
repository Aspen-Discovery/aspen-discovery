{strip}{if $showSeries}
	<div class="result-label col-tn-3">{translate text='Series'}</div>
	<div class="col-tn-9 result-value">
		{assign var=summSeries value=$series}
		{if $summSeries.fromNovelist}
			<a href="/GroupedWork/{$recordDriver->getPermanentId()}/Series">{$summSeries.seriesTitle}</a>{if $summSeries.volume} volume {$summSeries.volume}{/if}
		{else}
			<a href="/Search/Results?searchIndex=Series&lookfor={$summSeries.seriesTitle}">{$summSeries.seriesTitle}</a>{if $summSeries.volume} volume {$summSeries.volume}{/if}
		{/if}
		{if $indexedSeries}
			{if $summSeries.fromNovelist}
				<br/>
			{/if}
			{assign var=showMoreSeries value="false"}
			{if count($indexedSeries) >= 5}
				{assign var=showMoreSeries value="true"}
			{/if}
			{foreach from=$indexedSeries item=seriesItem name=loop}
				{if !isset($series.seriesTitle) || ((strpos(strtolower($seriesItem.seriesTitle), strtolower($series.seriesTitle)) === false) && (strpos(strtolower($series.seriesTitle), strtolower($seriesItem.seriesTitle)) === false))}
					<a href="/Search/Results?searchIndex=Series&lookfor=%22{$seriesItem.seriesTitle|removeTrailingPunctuation|escape:"url"}%22">{$seriesItem.seriesTitle|removeTrailingPunctuation|escape}</a>{if $seriesItem.volume} volume {$seriesItem.volume}{/if}<br/>
					{if !empty($showMoreSeries) && $smarty.foreach.loop.iteration == 3}
						<a onclick="$('#moreSeries_{$recordDriver->getPermanentId()}').show();$('#moreSeriesLink_{$recordDriver->getPermanentId()}').hide();" id="moreSeriesLink_{$summId}">{translate text="More Series..."}</a>
						<div id="moreSeries_{$recordDriver->getPermanentId()}" style="display:none">
					{/if}
				{/if}
			{/foreach}
			{if !empty($showMoreSeries)}
				</div>
			{/if}
		{/if}
	</div>
{/if}{/strip}