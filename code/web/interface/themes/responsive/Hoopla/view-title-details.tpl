{strip}

	{* Display more information about the title*}
	{if $recordDriver->getAuthor()}
		<div class="row">
			<div class="result-label col-md-3">Author: </div>
			<div class="col-md-9 result-value">
				<a href='{$path}/Author/Home?author="{$recordDriver->getAuthor()|escape:"url"}"'>{$recordDriver->getAuthor()|highlight}</a>
			</div>
		</div>
	{/if}

	{if $recordDriver->getSeries()}
		<div class="series row">
			<div class="result-label col-md-3">Series: </div>
			<div class="col-md-9 result-value">
				{assign var=summSeries value=$recordDriver->getSeries()}
				{if $summSeries->fromNovelist}
					<a href="{$path}/GroupedWork/{$recordDriver->getPermanentId()}/Series">{$summSeries.seriesTitle}</a>{if $summSeries.volume} volume {$summSeries.volume}{/if}
				{else}
					<a href="{$path}/Search/Results?lookfor={$summSeries.seriesTitle}">{$summSeries.seriesTitle}</a>{if $summSeries.volume} volume {$summSeries.volume}{/if}
				{/if}
			</div>
		</div>
	{/if}

	{if $showPublicationDetails && $recordDriver->getPublicationDetails()}
		<div class="row">
			<div class="result-label col-md-3">{translate text='Published'}:</div>
			<div class="col-md-9 result-value">
				{implode subject=$recordDriver->getPublicationDetails() glue=", "}
			</div>
		</div>
	{/if}

	{if $showPhysicalDespriptions && $recordDriver->getPhysicalDescriptions()}
		<div class="row">
			<div class="result-label col-md-3">{translate text='Physical Description'}:</div>
			<div class="col-md-9 result-value">
				{implode subject=$recordDriver->getPhysicalDescriptions() glue=", "}
			</div>
		</div>
	{/if}

	{if $showFormats}
		<div class="row">
			<div class="result-label col-md-3">{translate text='Format'}:</div>
			<div class="col-md-9 result-value">
				{implode subject=$recordDriver->getFormats() glue=", "}
			</div>
		</div>
	{/if}

	{if $showEditions && $recordDriver->getEdition()}
		<div class="row">
			<div class="result-label col-md-3">{translate text='Edition'}:</div>
			<div class="col-md-9 result-value">
				{implode subject=$recordDriver->getEdition() glue=", "}
			</div>
		</div>
	{/if}

	{if $showISBNs && count($recordDriver->getISBNs()) > 0}
		<div class="row">
			<div class="result-label col-xs-3">{translate text='ISBN'}:</div>
			<div class="col-xs-9 result-value">
				{implode subject=$recordDriver->getISBNs() glue=", "}
			</div>
		</div>
	{/if}

	{if $showArInfo && $recordDriver->getAcceleratedReaderDisplayString()}
		<div class="row">
			<div class="result-label col-md-3">{translate text='Accelerated Reader'}: </div>
			<div class="result-value col-md-9">
				{$recordDriver->getAcceleratedReaderDisplayString()}
			</div>
		</div>
	{/if}

	{if $showLexileInfo && $recordDriver->getLexileDisplayString()}
		<div class="row">
			<div class="result-label col-md-3">{translate text='Lexile measure'}: </div>
			<div class="result-value col-md-9">
				{$recordDriver->getLexileDisplayString()}
			</div>
		</div>
	{/if}

	{if $showFountasPinnell && $recordDriver->getFountasPinnellLevel()}
		<div class="row">
			<div class="result-label col-md-3">{translate text='Fountas &amp; Pinnell'}:</div>
			<div class="col-md-9 result-value">
				{$recordDriver->getFountasPinnellLevel()|escape}
			</div>
		</div>
	{/if}

	<div class="row">
		<div class="result-label col-md-3">{translate text='Status'}:</div>
		<div class="col-md-9 result-value result-value-bold statusValue here" id="statusValue">
			Available Online
		</div>
	</div>

{/strip}
