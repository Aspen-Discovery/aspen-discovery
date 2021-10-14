{strip}
	{if $recordDriver->getDetailedContributors()}
		<div class="row">
			<div class="result-label col-sm-3">{translate text='Contributors' isPublicFacing=true}</div>
			<div class="col-sm-9 result-value">
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

	{if !empty($recordDriver->getMpaaRating())}
		<div class="row">
			<div class="result-label col-sm-3">{translate text='Rating' isPublicFacing=true}</div>
			<div class="col-sm-9 result-value">{translate text=$recordDriver->getMpaaRating() isPublicFacing=true isMetadata=true}</div>
		</div>
	{/if}

	{if $recordDriver->getISBNs()}
		<div class="row">
			<div class="result-label col-sm-3">{translate text='ISBN' isPublicFacing=true}</div>
			<div class="col-sm-9 result-value">
				{foreach from=$recordDriver->getISBNs() item=tmpIsbn name=loop}
					{$tmpIsbn|escape}<br/>
				{/foreach}
			</div>
		</div>
	{/if}

	{if $recordDriver->getISSNs()}
		<div class="row">
			<div class="result-label col-sm-3">{translate text='ISSN' isPublicFacing=true}</div>
			<div class="col-sm-9 result-value">{implode subject=$recordDriver->getISSNs()}</div>
		</div>
	{/if}

	{if $recordDriver->getUPCs()}
		<div class="row">
			<div class="result-label col-sm-3">{translate text='UPC' isPublicFacing=true}</div>
			<div class="col-sm-9 result-value">
				{foreach from=$recordDriver->getUPCs() item=tmpUpc name=loop}
					{$tmpUpc|escape}<br/>
				{/foreach}
			</div>
		</div>
	{/if}

	{if $recordDriver->getAcceleratedReaderData() != null}
		{assign var="arData" value=$recordDriver->getAcceleratedReaderData()}
		<div class="row">
			<div class="result-label col-sm-3">{translate text='Accelerated Reader' isPublicFacing=true}</div>
			<div class="col-sm-9 result-value">
				{if $arData.interestLevel}
					{$arData.interestLevel|escape}<br/>
				{/if}
				{translate text="Level %1%, %2% Points" 1=$arData.readingLevel|escape 2=$arData.pointValue|escape isPublicFacing=true}
			</div>
		</div>
	{/if}

	{if $recordDriver->getLexileDisplayString()}
		<div class="row">
			<div class="result-label col-sm-3">{translate text='Lexile measure' isPublicFacing=true}</div>
			<div class="col-sm-9 result-value">
				{$recordDriver->getLexileDisplayString()|escape}
			</div>
		</div>
	{/if}

	{if $recordDriver->getFountasPinnellLevel()}
		<div class="row">
			<div class="result-label col-sm-3">{translate text='Fountas & Pinnell' isPublicFacing=true}</div>
			<div class="col-sm-9 result-value">
				{$recordDriver->getFountasPinnellLevel()|escape}
			</div>
		</div>
	{/if}

	{include file="GroupedWork/relatedLists.tpl"}

	{include file="GroupedWork/readingHistoryIndicator.tpl"}
{/strip}