{strip}
	{* Display more information about the title*}

	{if $recordDriver->getUniformTitle()}
		<div class="row">
			<div class="result-label col-tn-3">{translate text="Uniform Title" isPublicFacing=true} </div>
			<div class="col-tn-9 result-value">
				{foreach from=$recordDriver->getUniformTitle() item=uniformTitle}
					<a href="/Search/Results?lookfor={$uniformTitle|escape:"url"}">{$uniformTitle|highlight}</a><br/>
				{/foreach}
			</div>
		</div>
	{/if}

	{if $recordDriver->getAuthor()}
		<div class="row">
			<div class="result-label col-tn-3">{translate text="Author" isPublicFacing=true} </div>
			<div class="col-tn-9 result-value">
				<a href='/Author/Home?author="{$recordDriver->getAuthor()|escape:"url"}"'>{$recordDriver->getAuthor()|highlight}</a><br/>
			</div>
		</div>
	{/if}

	{if $recordDriver->getDetailedContributors()}
		<div class="row">
			<div class="result-label col-tn-3">{translate text='Contributors' isPublicFacing=true}</div>
			<div class="col-tn-9 result-value">
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
					{if $contributor.title}
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

	{if $showSeries}
		<div class="series row" id="seriesPlaceholder{$recordDriver->getPermanentId()}"></div>
	{/if}

	{if $showPublicationDetails && $recordDriver->getPublicationDetails()}
		<div class="row">
			<div class="result-label col-tn-3">{translate text='Published' isPublicFacing=true}</div>
			<div class="col-tn-9 result-value">
				{implode subject=$recordDriver->getPublicationDetails() glue=", "}
			</div>
		</div>
	{/if}

	{if $showFormats}
	<div class="row">
		<div class="result-label col-tn-3">{translate text='Format' isPublicFacing=true}</div>
		<div class="col-tn-9 result-value">
			{implode subject=$recordFormat glue=", ", translate=true}
		</div>
	</div>
	{/if}

	{if $showEditions && $recordDriver->getEditions()}
		<div class="row">
			<div class="result-label col-tn-3">{translate text='Edition' isPublicFacing=true}</div>
			<div class="col-tn-9 result-value">
				{implode subject=$recordDriver->getEditions() glue=", "}
			</div>
		</div>
	{/if}

	{if $showISBNs && count($recordDriver->getISBNs()) > 0}
		<div class="row">
			<div class="result-label col-tn-3">{translate text='ISBN' isPublicFacing=true}</div>
			<div class="col-tn-9 result-value">
				{implode subject=$recordDriver->getISBNs() glue=", "}
			</div>
		</div>
	{/if}

	{if $showISBNs && count($recordDriver->getISSNs()) > 0}
		{if $recordDriver->getISSNs()}
			<div class="row">
				<div class="result-label col-md-3">{translate text='ISSN' isPublicFacing=true}</div>
				<div class="col-md-9 result-value">{implode subject=$recordDriver->getISSNs()}</div>
			</div>
		{/if}
	{/if}

	{if $showPhysicalDescriptions && $physicalDescriptions}
		<div class="row">
			<div class="result-label col-tn-3">{translate text='Physical Desc' isPublicFacing=true}</div>
			<div class="col-tn-9 result-value">
				{implode subject=$physicalDescriptions glue="<br>"}
			</div>
		</div>
	{/if}

	{if !empty($showArInfo) && $recordDriver->getAcceleratedReaderDisplayString()}
		<div class="row">
			<div class="result-label col-tn-3">{translate text='Accelerated Reader' isPublicFacing=true} </div>
			<div class="result-value col-tn-9">
				{$recordDriver->getAcceleratedReaderDisplayString()}
			</div>
		</div>
	{/if}

	{if !empty($showLexileInfo) && $recordDriver->getLexileDisplayString()}
		<div class="row">
			<div class="result-label col-tn-3">{translate text='Lexile measure' isPublicFacing=true} </div>
			<div class="result-value col-tn-9">
				{$recordDriver->getLexileDisplayString()}
			</div>
		</div>
	{/if}

	{if !empty($showFountasPinnell) && $recordDriver->getFountasPinnellLevel()}
		<div class="row">
			<div class="result-label col-tn-3">{translate text='Fountas & Pinnell' isPublicFacing=true}</div>
			<div class="col-tn-9 result-value">
				{$recordDriver->getFountasPinnellLevel()|escape}
			</div>
		</div>
	{/if}

	{if $mpaaRating}
		<div class="row">
			<div class="result-label col-tn-3">{translate text='Rating' isPublicFacing=true}</div>
			<div class="col-tn-9 result-value">{$mpaaRating|escape}</div>
		</div>
	{/if}

	{include file="GroupedWork/relatedLists.tpl"}

	{include file="GroupedWork/readingHistoryIndicator.tpl"}

	{* Detailed status information *}
	<div class="row">
		<div class="result-label col-tn-3">{translate text='Status' isPublicFacing=true}</div>
		<div class="col-tn-9 result-value">
			{if $statusSummary}
				{assign var=workId value=$recordDriver->getPermanentId()}
				{include file='GroupedWork/statusIndicator.tpl' statusInformation=$statusSummary->getStatusInformation() viewingIndividualRecord=1}
				{if (!$statusSummaryh->isEContent)}
					{include file='GroupedWork/copySummary.tpl' summary=$statusSummary->getItemSummary() totalCopies=$statusSummary->getCopies() itemSummaryId=$statusSummary->id format=$recordDriver->getPrimaryFormat()}
				{/if}
			{else}
				{translate text="Unavailable/Withdrawn" isPublicFacing=true}
			{/if}

		</div>
	</div>
{/strip}
