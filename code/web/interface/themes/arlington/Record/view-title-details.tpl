{strip}
	{* Display more information about the title*}

	{if $recordDriver->getUniformTitle()}
		<div class="row">
			<div class="result-label col-sm-4">Uniform Title: </div>
			<div class="col-sm-8 result-value">
				{foreach from=$recordDriver->getUniformTitle() item=uniformTitle}
					<a href="{$path}/Search/Results?lookfor={$uniformTitle|escape:"url"}">{$uniformTitle|highlight}</a><br/>
				{/foreach}
			</div>
		</div>
	{/if}

	{if $recordDriver->getAuthor()}
		<div class="row">
			<div class="result-label col-sm-4">Author: </div>
			<div class="col-sm-8 result-value">
				<a href='{$path}/Author/Home?author="{$recordDriver->getAuthor()|escape:"url"}"'>{$recordDriver->getAuthor()|highlight}</a><br/>
			</div>
		</div>
	{/if}

	{if $recordDriver->getDetailedContributors()}
		<div class="row">
			<div class="result-label col-sm-4">{translate text='Contributors'}:</div>
			<div class="col-sm-8 result-value">
				{foreach from=$recordDriver->getDetailedContributors() item=contributor name=loop}
					{if $smarty.foreach.loop.index == 5}
						<div id="showAdditionalContributorsLink">
							<a onclick="VuFind.Record.moreContributors(); return false;" href="#">{translate text='more'} ...</a>
						</div>
						{*create hidden div*}
						<div id="additionalContributors" style="display:none">
					{/if}
					<a href='{$path}/Author/Home?author="{$contributor.name|trim|escape:"url"}"'>{$contributor.name|escape}</a>
					{if $contributor.role}
						&nbsp;{$contributor.role}
					{/if}
					{if $contributor.title}
						&nbsp;<a href="{$path}/Search/Results?lookfor={$contributor.title}&amp;basicType=Title">{$contributor.title}</a>
					{/if}
				<br/>
				{/foreach}
				{if $smarty.foreach.loop.index >= 5}
					<div>
						<a href="#" onclick="VuFind.Record.lessContributors(); return false;">{translate text='less'} ...</a>
					</div>
					</div>{* closes hidden div *}
				{/if}
			</div>
		</div>
	{/if}

	{if $recordDriver->getSeries()}
		<div class="series row">
			<div class="result-label col-sm-4">Series: </div>
			<div class="col-sm-8 result-value">
				{assign var=summSeries value=$recordDriver->getSeries()}
				<a href="{$path}/GroupedWork/{$recordDriver->getPermanentId()}/Series">{$summSeries.seriesTitle}</a>{if !empty($seriesItem.volume)} volume {$summSeries.volume}{/if}
			</div>
		</div>
	{/if}

	{if $showPublicationDetails && $recordDriver->getPublicationDetails()}
		<div class="row">
			<div class="result-label col-sm-4">{translate text='Published'}:</div>
			<div class="col-sm-8 result-value">
				{implode subject=$recordDriver->getPublicationDetails() glue=", "}
			</div>
		</div>
	{/if}

	{if $showFormats}
	<div class="row">
		<div class="result-label col-sm-4">{translate text='Format'}:</div>
		<div class="col-sm-8 result-value">
			{implode subject=$recordFormat glue=", "}
		</div>
	</div>
	{/if}

	{if $showEditions && $recordDriver->getEdition()}
		<div class="row">
			<div class="result-label col-sm-4">{translate text='Edition'}:</div>
			<div class="col-sm-8 result-value">
				{implode subject=$recordDriver->getEdition() glue=", "}
			</div>
		</div>
	{/if}

	{if $showISBNs && count($recordDriver->getISBNs()) > 0}
		<div class="row">
			<div class="result-label col-sm-4">{translate text='ISBN'}:</div>
			<div class="col-sm-8 result-value">
				{implode subject=$recordDriver->getISBNs() glue=", "}
			</div>
		</div>
	{/if}

	{if $recordDriver->getISSNs()}
		<div class="row">
			<div class="result-label col-md-3">{translate text='ISSN'}:</div>
			<div class="col-md-9 result-value">{implode subject=$recordDriver->getISSNs()}</div>
		</div>
	{/if}

	{if $showPhysicalDescriptions && $physicalDescriptions}
		<div class="row">
			<div class="result-label col-sm-4">{translate text='Physical Desc'}:</div>
			<div class="col-sm-8 result-value">
				{implode subject=$physicalDescriptions glue="<br>"}
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
			<div class="result-label col-sm-4">{translate text='Fountas &amp; Pinnell Level'}:</div>
			<div class="col-sm-8 result-value">
				{$recordDriver->getFountasPinnellLevel()|escape}
			</div>
		</div>
	{/if}

	{if $mpaaRating}
		<div class="row">
			<div class="result-label col-sm-4">{translate text='Rating'}:</div>
			<div class="col-sm-8 result-value">{$mpaaRating|escape}</div>
		</div>
	{/if}

	{* Detailed status information *}
	<div class="row">
		<div class="result-label col-sm-4">{translate text='Status'}:</div>
		<div class="col-sm-8 result-value">
			{include file='GroupedWork/statusIndicator.tpl' statusInformation=$statusSummary viewingIndividualRecord=1}

			{include file='GroupedWork/copySummary.tpl' summary=$statusSummary.itemSummary totalCopies=$statusSummary.copies itemSummaryId=$statusSummary.id format=$recordDriver->getPrimaryFormat() recordUrl=$recordDriver->getRecordUrl()}
		</div>
		{* <div class="col-xs-8 result-value result-value-bold statusValue {$statusSummary.class}" id="statusValue">{$statusSummary.groupedStatus|escape}{if $statusSummary.numHolds > 0} ({$statusSummary.numHolds} people are on the wait list){/if}</div> *}
	</div>

{/strip}
