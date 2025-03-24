{strip}
	<div>
		{if $recordDriver->getPrimaryAuthor()}
			<div class="row">
				<div class="result-label col-md-3">{translate text="Author" isPublicFacing=true} </div>
				<div class="col-md-9 result-value notranslate">
					<a href='/Author/Home?author="{$recordDriver->getPrimaryAuthor()|escape:"url"}"'>{$recordDriver->getPrimaryAuthor()|highlight}</a>
				</div>
			</div>
		{/if}
		{if $recordDriver->hasCachedSeries()}
			{assign var=summSeries value=$recordDriver->getSeries(false)}
			{if !empty($summSeries)}
				<div class="series row">
					<div class="result-label col-md-3">{translate text="Series" isPublicFacing=true} </div>
					<div class="col-md-9 result-value">
						{if !empty($summSeries.fromNovelist)}
							<a href="/GroupedWork/{$recordDriver->getPermanentId()}/Series">{$summSeries.seriesTitle}</a>{if !empty($summSeries.volume)}<strong> {translate text="volume %1%" 1=$summSeries.volume|format_float_with_min_decimals isPublicFacing=true}</strong>{/if}
						{elseif !empty($summSeries.fromSeriesIndex)}
							<a href="/Series/{$summSeries.seriesId}">{$summSeries.seriesTitle}</a>{if !empty($summSeries.volume)}<strong> {translate text="volume %1%" 1=$summSeries.volume|format_float_with_min_decimals isPublicFacing=true}</strong>{/if}
						{else}
							<a href="/Search/Results?searchIndex=Series&lookfor={$summSeries.seriesTitle}&sort=year+asc%2Ctitle+asc">{$summSeries.seriesTitle}</a>{if !empty($summSeries.volume)}<strong> {translate text="volume %1%" 1=$summSeries.volume|format_float_with_min_decimals isPublicFacing=true}</strong>{/if}
						{/if}
					</div>
				</div>
			{/if}
		{/if}
		{if $recordDriver->getDescriptionFast()}
			<div class="row">
				<div class="col-sm-12">
					<span class="result-label">{translate text="Description" isPublicFacing=true} </span>
				</div>
				<div class="col-sm-12">
					{$recordDriver->getDescriptionFast()|stripTags:'<b><p><i><em><strong><ul><li><ol>'}{*Leave unescaped because some syndetics reviews have html in them *}
				</div>
			</div>
		{/if}
		{include file="GroupedWork/relatedManifestations.tpl" relatedManifestations=$recordDriver->getRelatedManifestations() inPopUp=true id=$recordDriver->getPermanentId() summTitle=$recordDriver->getTitle()}
	</div>
{/strip}
