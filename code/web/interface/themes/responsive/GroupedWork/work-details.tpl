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
			{if !empty($summSeries) && empty($summSeries.allHidden)}
				<div class="series row">
					<div class="result-label col-md-3">{translate text="Series" isPublicFacing=true} </div>
					<div class="col-md-9 result-value">
						{assign var=seriesLimit value=$numSeriesToShowBeforeMore+1}
						{assign var=totalSeriesShown value=0}
						{include "GroupedWork/series-shared.tpl" summSeries=$summSeries seriesLimit=$seriesLimit}
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
		{include file="GroupedWork/allManifestations.tpl" relatedManifestations=$recordDriver->getRelatedManifestations() inPopUp=true summId=$recordDriver->getPermanentId() id=$recordDriver->getPermanentId() summTitle=$recordDriver->getTitle() isSearchResults=true}
	</div>
{/strip}
