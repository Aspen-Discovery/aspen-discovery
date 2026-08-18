{strip}
	{* Display more information about the title*}
	{if $recordDriver->getAuthor()}
		<div class="full-record-author row">
			<div class="result-label col-sm-4 col-xs-12">{translate text=Author isPublicFacing=true} </div>
			<div class="result-value col-sm-8 col-xs-12">
				<a href='/Author/Home?author="{$recordDriver->getAuthor()|escape:"url"}"'>{$recordDriver->getAuthor()|highlight}</a>
			</div>
		</div>
	{/if}

	{if !empty($showSeries)}
		<div class="full-record-series series row" id="seriesPlaceholder{$recordDriver->getPermanentId()}"></div>
	{/if}

	{if !empty($showPublicationDetails) && $recordDriver->getPublicationDetails()}
		<div class="full-record-publication-details row">
			<div class="result-label col-sm-4 col-xs-12">{translate text='Published' isPublicFacing=true}</div>
			<div class="result-value col-sm-8 col-xs-12">
				{implode subject=$recordDriver->getPublicationDetails() glue=", "}
			</div>
		</div>
	{/if}

	{if !empty($showFormats)}
		<div class="full-record-format row">
			<div class="result-label col-sm-4 col-xs-12">{translate text='Format' isPublicFacing=true}</div>
			<div class="result-value col-sm-8 col-xs-12">
				{implode subject=$recordDriver->getFormats() glue=", "}
			</div>
		</div>
	{/if}

	{if !empty($showEditions) && $recordDriver->getEditions()}
		<div class="full-record-edition row">
			<div class="result-label col-sm-4 col-xs-12">{translate text='Edition' isPublicFacing=true}</div>
			<div class="col-sm-9 result-value">
				{implode subject=$recordDriver->getEditions() glue=", "}
			</div>
		</div>
	{/if}

	{if !empty($showISBNs) && count($recordDriver->getISBNs()) > 0}
		<div class="full-record-isbn row">
			<div class="result-label col-sm-4 col-xs-12">{translate text='ISBN' isPublicFacing=true}</div>
			<div class="result-value col-sm-8 col-xs-12">
				{implode subject=$recordDriver->getISBNs() glue=", "}
			</div>
		</div>
	{/if}

	{if !empty($showArInfo) && $recordDriver->getAcceleratedReaderDisplayString()}
		<div class="full-record-accelerated-reader row">
			<div class="result-label col-sm-4 col-xs-12">{translate text='Accelerated Reader' isPublicFacing=true} </div>
			<div class="result-value col-sm-8 col-xs-12">
				{$recordDriver->getAcceleratedReaderDisplayString()}
			</div>
		</div>
	{/if}

	{if !empty($showLexileInfo) && $recordDriver->getLexileDisplayString()}
		<div class="full-record-lexile row">
			<div class="result-label col-sm-4 col-xs-12">{translate text='Lexile measure' isPublicFacing=true} </div>
			<div class="result-value col-sm-8 col-xs-12">
				{$recordDriver->getLexileDisplayString()}
			</div>
		</div>
	{/if}

	{if !empty($showFountasPinnell) && $recordDriver->getFountasPinnellLevel()}
		<div class="full-record-fountas-pinnell row">
			<div class="result-label col-sm-4 col-xs-12">{translate text='Fountas & Pinnell' isPublicFacing=true}</div>
			<div class="result-value col-sm-8 col-xs-12">
				{$recordDriver->getFountasPinnellLevel()|escape}
			</div>
		</div>
	{/if}

	{include file="GroupedWork/relatedLists.tpl" isSearchResults=false}

	{include file="GroupedWork/readingHistoryIndicator.tpl" isSearchResults=false}

	<div class="full-record-status row">
		<div class="result-label col-sm-4 col-xs-12">{translate text='Status' isPublicFacing=true}</div>
		<div class="result-value col-sm-8 col-xs-12 result-value-bold statusValue here" id="statusValue">{translate text="Available Online" isPublicFacing=true}</div>
	</div>

{/strip}