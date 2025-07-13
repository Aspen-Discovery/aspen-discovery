{strip}
	{* Display more information about the title*}

	{if $recordDriver->getUniformTitle()}
		<div class="row">
			<div class="result-label col-sm-4 col-xs-12">{translate text="Uniform Title" isPublicFacing=true} </div>
			<div class="result-value col-sm-8 col-xs-12">
				{foreach from=$recordDriver->getUniformTitle() item=uniformTitle}
					<a href="/Search/Results?lookfor={$uniformTitle|escape:"url"}">{$uniformTitle|highlight}</a><br/>
				{/foreach}
			</div>
		</div>
	{/if}

	{if $recordDriver->getAuthor()}
		<div class="row">
			<div class="result-label col-sm-4 col-xs-12">{translate text="Author" isPublicFacing=true} </div>
			<div class="result-value col-sm-8 col-xs-12">
				<a href='/Author/Home?author="{$recordDriver->getAuthor()|escape:"url"}"'>{$recordDriver->getAuthor()|highlight}</a>{if !empty($recordDriver->get880Authors())} <span class="agrAuthor">({implode subject=$recordDriver->get880Authors() glue=',' removeTrailingPunctuationFromTerms=true})</span>{/if}<br/>
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
					{if $contributor.isAgr}
						<div class="agrContributor">
					{else}
						<div class="contributor">
					{/if}
					<a href='/Author/Home?author="{$contributor.name|trim|escape:"url"}"'>{$contributor.name|escape}</a>
					{if !empty($contributor.roles)}
						&nbsp;{implode subject=$contributor.roles glue=", " translate=true isPublicFacing=true removeTrailingPunctuationFromTerms=true}
					{/if}
					{if !empty($contributor.title)}
						&nbsp;<a href="/Search/Results?lookfor={$contributor.title}&amp;searchIndex=Title">{$contributor.title}</a>
					{/if}
						{if !empty($contributor.agr)}
							<span class="agrContributor">
								&nbsp;({$contributor.agr.name|escape}
								{if !empty($contributor.agr.roles)}
									&nbsp;{implode subject=$contributor.agr.roles glue=", " translate=true isPublicFacing=true removeTrailingPunctuationFromTerms=true}
								{/if}
								{if !empty($contributor.agr.title)}
									&nbsp;{$contributor.agr.title}
								{/if}
								)
							</span>
						{/if}
					</div>
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
			{implode subject=$recordFormat glue=", " translate=true isPublicFacing=true}
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

	{if !empty($showISBNs) && count($recordDriver->getISSNs()) > 0}
		{if $recordDriver->getISSNs()}
			<div class="row">
				<div class="result-label col-sm-4 col-xs-12">{translate text='ISSN' isPublicFacing=true}</div>
				<div class="result-value col-sm-8 col-xs-12">{implode subject=$recordDriver->getISSNs()}</div>
			</div>
		{/if}
	{/if}

	{if !empty($showPhysicalDescriptions) && !empty($physicalDescriptions)}
		<div class="row">
			<div class="result-label col-sm-4 col-xs-12">{translate text='Physical Desc' isPublicFacing=true}</div>
			<div class="result-value col-sm-8 col-xs-12">
				{implode subject=$physicalDescriptions glue="<br>"}
				{if $recordDriver->isClosedCaptioned()}
					&nbsp;<i class="fas fa-closed-captioning"></i>
				{/if}
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

	{if !empty($mpaaRating)}
		<div class="row">
			<div class="result-label col-sm-4 col-xs-12">{translate text='Rating' isPublicFacing=true}</div>
			<div class="result-value col-sm-8 col-xs-12">{$mpaaRating|escape}</div>
		</div>
	{/if}

	{if !empty($showAudience) && $recordDriver->getAudience()}
		<div class="row">
			<div class="result-label col-sm-4 col-xs-12">{translate text='Audience' isPublicFacing=true} </div>
			<div class="result-value col-sm-8 col-xs-12">
				{$recordDriver->getAudience()}
			</div>
		</div>
	{/if}

	{include file="GroupedWork/relatedLists.tpl" isSearchResults=false}

	{include file="GroupedWork/readingHistoryIndicator.tpl" isSearchResults=false}

	{if !($recordDriver->hasMultipleVariations())}
	{* Detailed status information *}
	<div class="row">
		<div class="result-label col-sm-4 col-xs-12">{translate text='Status' isPublicFacing=true}</div>
		<div class="result-value col-sm-8 col-xs-12">
			{if !empty($statusSummary)}
				{assign var=workId value=$recordDriver->getPermanentId()}
				{include file='GroupedWork/statusIndicator.tpl' statusInformation=$statusSummary->getStatusInformation() viewingIndividualRecord=1}
				{if ($statusSummary->showCopySummary())}
					{include file='GroupedWork/copySummary.tpl' summary=$statusSummary->getItemSummary() totalCopies=$statusSummary->getCopies() itemSummaryId=$statusSummary->id format=$recordDriver->getPrimaryFormat() isEContent=$statusSummary->isEContent()}
				{/if}
			{else}
				{translate text="Unavailable/Withdrawn" isPublicFacing=true}
			{/if}

		</div>
	</div>
	{/if}
{/strip}
