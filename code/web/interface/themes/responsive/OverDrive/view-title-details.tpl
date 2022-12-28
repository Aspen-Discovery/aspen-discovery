{strip}
	{* Display more information about the title*}
	{if $recordDriver->getAuthor()}
		<div class="row">
			<div class="result-label col-sm-4 col-xs-12">{translate text="Author" isPublicFacing=true} </div>
			<div class="result-value col-sm-8 col-xs-12">
				<a href='/Author/Home?author="{$recordDriver->getAuthor()|escape:"url"}"'>{$recordDriver->getAuthor()|highlight}</a>
			</div>
		</div>
	{/if}

	{if $recordDriver->getDetailedContributors()}
		<div class="row">
			<div class="result-label col-sm-4 col-xs-12">{translate text='Contributors' isPublicFacing=true}</div>
			<div class="result-value col-sm-8 col-xs-12">
				{foreach from=$recordDriver->getDetailedContributors() item=contributor name=loop}
					{if $smarty.foreach.loop.index == 5}
						<div id="showAdditionalContributorsLink">
							<a onclick="AspenDiscovery.Record.moreContributors(); return false;" href="#">{translate text='more' isPublicFacing=true} ...</a>
						</div>
						{*create hidden div*}
						<div id="additionalContributors" style="display:none">
					{/if}
					<a href='/Author/Home?author="{$contributor.name|trim|escape:"url"}"'>{$contributor.name|escape}</a>
					{if !empty($contributor.roles)}
						&nbsp;{implode subject=$contributor.roles glue=", " translate=true isPublicFacing=true}
					{/if}
					{if !empty($contributor.title)}
						&nbsp;<a href="/Search/Results?lookfor={$contributor.title}&amp;searchIndex=Title">{$contributor.title}</a>
					{/if}
				<br/>
				{/foreach}
				{if $smarty.foreach.loop.index >= 5}
					<div>
						<a href="#" onclick="AspenDiscovery.Record.lessContributors(); return false;">{translate text='less' isPublicFacing=true} ...</a>
					</div>
					</div>{* closes hidden div *}
				{/if}
			</div>
		</div>
	{/if}

	{if !empty($showSeries)}
		<div class="series row" id="seriesPlaceholder{$recordDriver->getPermanentId()}"></div>
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
				{implode subject=$recordDriver->getFormats() glue=", "}
			</div>
		</div>
	{/if}

	{if !empty($showEditions) && $recordDriver->getEditions()}
		<div class="row">
			<div class="result-label col-sm-4 col-xs-12">{translate text='Edition' isPublicFacing=true}</div>
			<div class="result-value col-sm-8 col-xs-12">
				{implode subject=$recordDriver->getEditions() glue=", "}
			</div>
		</div>
	{/if}


	{if !empty($showISBNs) && count($recordDriver->getISBNs()) > 0}
		<div class="row">
			<div class="result-label col-sm-4 col-xs-12">{translate text='ISBN' isPublicFacing=true}</div>
			<div class="result-value col-sm-8 col-xs-12">
				{implode subject=$recordDriver->getISBNs() glue=", "}
			</div>
		</div>
	{/if}

	{if !empty($showArInfo) && $recordDriver->getAcceleratedReaderDisplayString()}
		<div class="row">
			<div class="result-label col-sm-4 col-xs-12">{translate text='Accelerated Reader' isPublicFacing=true} </div>
			<div class="result-value col-sm-8 col-xs-12">
				{$recordDriver->getAcceleratedReaderDisplayString()}
			</div>
		</div>
	{/if}

	{if !empty($showLexileInfo) && $recordDriver->getLexileDisplayString()}
		<div class="row">
			<div class="result-label col-sm-4 col-xs-12">{translate text='Lexile measure' isPublicFacing=true} </div>
			<div class="result-value col-sm-8 col-xs-12">
				{$recordDriver->getLexileDisplayString()}
			</div>
		</div>
	{/if}

	{if !empty($showFountasPinnell) && $recordDriver->getFountasPinnellLevel()}
		<div class="row">
			<div class="result-label col-sm-4 col-xs-12">{translate text='Fountas & Pinnell' isPublicFacing=true}</div>
			<div class="result-value col-sm-8 col-xs-12">
				{$recordDriver->getFountasPinnellLevel()|escape}
			</div>
		</div>
	{/if}

	{include file="GroupedWork/relatedLists.tpl" isSearchResults=false}

	{include file="GroupedWork/readingHistoryIndicator.tpl"}

	<div class="row">
		<div class="result-label col-sm-4 col-xs-12">{translate text='Status' isPublicFacing=true}</div>
		<div class="result-value col-sm-8 col-xs-12 result-value-bold statusValue {$holdingsSummary.class}" id="statusValue">{translate text=$holdingsSummary.status isPublicFacing=true}</div>
	</div>

{/strip}
