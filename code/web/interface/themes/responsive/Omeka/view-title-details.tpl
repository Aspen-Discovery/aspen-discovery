{strip}
	{if $recordDriver->getPrimaryAuthor()}
		<div class="row">
			<div class="result-label col-sm-4 col-xs-12">{translate text="Author" isPublicFacing=true}</div>
			<div class="result-value col-sm-8 col-xs-12">
				<a href='/Author/Home?author="{$recordDriver->getPrimaryAuthor()|escape:"url"}"'>{$recordDriver->getPrimaryAuthor()|highlight}</a>
			</div>
		</div>
	{/if}

	{if !empty($showPublicationDetails) && $recordDriver->getPublicationDetails()}
		<div class="row">
			<div class="result-label col-sm-4 col-xs-12">{translate text='Published' isPublicFacing=true}</div>
			<div class="result-value col-sm-8 col-xs-12">
				{implode subject=$recordDriver->getPublicationDetails() glue=", "}
			</div>
		</div>
	{/if}

	{if !empty($showFormats)}
		<div class="row">
			<div class="result-label col-sm-4 col-xs-12">{translate text='Format' isPublicFacing=true}</div>
			<div class="result-value col-sm-8 col-xs-12">
				{implode subject=$recordDriver->getFormats() glue=", " translate=true isPublicFacing=true}
			</div>
		</div>
	{/if}

	{include file="GroupedWork/relatedLists.tpl" isSearchResults=false}

	{include file="GroupedWork/readingHistoryIndicator.tpl" isSearchResults=false}

	<div class="row">
		<div class="result-label col-sm-4 col-xs-12">{translate text='Status' isPublicFacing=true}</div>
		<div class="result-value col-sm-8 col-xs-12 result-value-bold statusValue {$holdingsSummary.class}" id="statusValue">{translate text=$holdingsSummary.status isPublicFacing=true}</div>
	</div>
{/strip}
