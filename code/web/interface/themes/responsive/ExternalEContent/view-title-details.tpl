{strip}
	{* Display more information about the title*}
	{if $recordDriver->getAuthor()}
		<div class="row">
			<div class="result-label col-sm-3">Author: </div>
			<div class="col-sm-9 result-value">
				<a href='{$path}/Author/Home?author="{$recordDriver->getAuthor()|escape:"url"}"'>{$recordDriver->getAuthor()|highlight}</a>
			</div>
		</div>
	{/if}

	{assign var=series value=$recordDriver->getSeries()}
	{if $series}
		<div class="series row">
			<div class="result-label col-sm-3">Series: </div>
			<div class="col-sm-9 result-value">
				{if is_array($series) && !isset($series.seriesTitle)}
					{foreach from=$series item=seriesItem name=loop}
						<a href="{$path}/Search/Results?basicType=Series&lookfor=%22{$seriesItem.seriesTitle|removeTrailingPunctuation|escape:"url"}%22">{$seriesItem.seriesTitle|removeTrailingPunctuation|escape}</a>{if $seriesItem.volume} volume {$seriesItem.volume}{/if}<br/>
					{/foreach}
				{else}
					<a href="{$path}/Search/Results?basicType=Series&lookfor=%22{$series.seriesTitle|removeTrailingPunctuation|escape:"url"}%22">{$series.seriesTitle|removeTrailingPunctuation|escape}</a>{if $series.volume} volume {$series.volume}{/if}<br/>
				{/if}
			</div>
		</div>
	{/if}

	{if $showPublicationDetails && $recordDriver->getPublicationDetails()}
		<div class="row">
			<div class="result-label col-sm-3">{translate text='Published'}:</div>
			<div class="col-sm-9 result-value">
				{implode subject=$recordDriver->getPublicationDetails() glue=", "}
			</div>
		</div>
	{/if}

	{if $showFormats}
		<div class="row">
			<div class="result-label col-sm-3">{translate text='Format'}:</div>
			<div class="col-sm-9 result-value">
				{implode subject=$recordDriver->getFormats() glue=", "}
			</div>
		</div>
	{/if}

	{if $showEditions && $recordDriver->getEdition()}
		<div class="row">
			<div class="result-label col-sm-3">{translate text='Edition'}:</div>
			<div class="col-sm-9 result-value">
				{implode subject=$recordDriver->getEdition() glue=", "}
			</div>
		</div>
	{/if}

	{if $showISBNs && count($recordDriver->getISBNs()) > 0}
		<div class="row">
			<div class="result-label col-sm-3">{translate text='ISBN'}:</div>
			<div class="col-sm-9 result-value">
				{implode subject=$recordDriver->getISBNs() glue=", "}
			</div>
		</div>
	{/if}

	{if $showArInfo && $recordDriver->getAcceleratedReaderDisplayString()}
		<div class="row">
			<div class="result-label col-sm-4">{translate text='Accelerated Reader'}: </div>
			<div class="result-value col-sm-8">
				{$recordDriver->getAcceleratedReaderDisplayString()}
			</div>
		</div>
	{/if}

	{if $showLexileInfo && $recordDriver->getLexileDisplayString()}
		<div class="row">
			<div class="result-label col-sm-4">{translate text='Lexile measure'}: </div>
			<div class="result-value col-sm-8">
				{$recordDriver->getLexileDisplayString()}
			</div>
		</div>
	{/if}

	{if $showFountasPinnell && $recordDriver->getFountasPinnellLevel()}
		<div class="row">
			<div class="result-label col-sm-3>{translate text='Fountas &amp; Pinnell'}:</div>
			<div class="col-sm-9 result-value">
				{$recordDriver->getFountasPinnellLevel()|escape}
			</div>
		</div>
	{/if}

	<div class="row">
		<div class="result-label col-sm-3">{translate text='Status'}:</div>
		<div class="col-sm-9 result-value result-value-bold statusValue here" id="statusValue">Available Online</div>
	</div>

{/strip}
