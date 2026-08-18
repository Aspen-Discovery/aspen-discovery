{strip}
	{* Details not shown in the Top/Main Section of the Record view should be shown here *}
	{if !empty($recordDriver) && empty($showPublicationDetails) && $recordDriver->getPublicationDetails()}
		<div class="full-record-property full-record-publication-details row">
			<div class="result-label col-xs-3">{translate text='Published' isPublicFacing=true}</div>
			<div class="col-xs-9 result-value">
				{implode subject=$recordDriver->getPublicationDetails() glue=", "}
			</div>
		</div>
	{/if}

	{if empty($showFormats)}
		<div class="full-record-property full-record-format row">
			<div class="result-label col-xs-3">{translate text='Format' isPublicFacing=true}</div>
			<div class="col-xs-9 result-value">
				{implode subject=$recordFormat glue=", "}
			</div>
		</div>
	{/if}

	{if !empty($recordDriver) && empty($showEditions) && $recordDriver->getEditions()}
		<div class="full-record-property full-record-edition row">
			<div class="result-label col-xs-3">{translate text='Edition' isPublicFacing=true}</div>
			<div class="col-xs-9 result-value">
				{implode subject=$recordDriver->getEditions() glue=", "}
			</div>
		</div>
	{/if}

	{if empty($showPhysicalDescriptions) && !empty($physicalDescriptions)}
		<div class="full-record-property full-record-physical-description row">
			<div class="result-label col-xs-3">{translate text='Physical Desc' isPublicFacing=true}</div>
			<div class="col-xs-9 result-value">
				{implode subject=$physicalDescriptions glue="<br/>"}
			</div>
		</div>
	{/if}

	{if !empty($streetDate)}
		<div class="full-record-street-date row">
			<div class="result-label col-xs-3">{translate text='Street Date' isPublicFacing=true}</div>
			<div class="col-xs-9 result-value">
				{$streetDate|escape}
			</div>
		</div>
	{/if}

	<div class="full-record-property full-record-language row">
		<div class="result-label col-xs-3">{translate text='Language' isPublicFacing=true}</div>
		<div class="col-xs-9 result-value">
			{implode subject=$recordLanguage glue=", "}
		</div>
	</div>

	{if !empty($recordDriver) && empty($showISBNs)}
		{if count($recordDriver->getISBNs()) > 0}
			<div class="full-record-property full-record-isbn row">
				<div class="result-label col-xs-3">{translate text='ISBN' isPublicFacing=true}</div>
				<div class="col-xs-9 result-value">
					{implode subject=$recordDriver->getISBNs() glue=", "}
				</div>
			</div>
		{/if}
		{if count($recordDriver->getISSNs()) > 0}
			<div class="full-record-issn row">
				<div class="result-label col-xs-3">{translate text='ISSN' isPublicFacing=true}</div>
				<div class="col-xs-9 result-value">
					{implode subject=$recordDriver->getISSNs() glue=", "}
				</div>
			</div>
		{/if}
	{/if}

	{if !empty($recordDriver) && count($recordDriver->getUPCs()) > 0}
		<div class="full-record-upc row">
			<div class="result-label col-xs-3">{translate text='UPC' isPublicFacing=true}</div>
			<div class="col-xs-9 result-value">
				{implode subject=$recordDriver->getUPCs() glue=", "}
			</div>
		</div>
	{/if}

	{if !empty($recordDriver) && $recordDriver->getAcceleratedReaderData() != null}
		{assign var="arData" value=$recordDriver->getAcceleratedReaderData()}
		<div class="full-record-property full-record-accelerated-reader row">
			<div class="result-label col-xs-3">{translate text='Accelerated Reader' isPublicFacing=true}</div>
			<div class="col-xs-9 result-value">
				{$arData.interestLevel|escape}<br/>
				{translate text="Level %1%, %2% Points" 1=$arData.readingLevel|escape 2=$arData.pointValue|escape isPublicFacing=true}
			</div>
		</div>
	{/if}

	{if !empty($recordDriver) && $recordDriver->getLexileCode()}
		<div class="full-record-property full-record-lexile-code row">
			<div class="result-label col-xs-3">{translate text='Lexile code' isPublicFacing=true}</div>
			<div class="col-xs-9 result-value">
				{$recordDriver->getLexileCode()|escape}
			</div>
		</div>
	{/if}

	{if !empty($recordDriver) && $recordDriver->getLexileScore()}
		<div class="full-record-property full-record-lexile-measure row">
			<div class="result-label col-xs-3">{translate text='Lexile measure' isPublicFacing=true}</div>
			<div class="col-xs-9 result-value">
				{$recordDriver->getLexileScore()|escape}
			</div>
		</div>
	{/if}

	{if !empty($recordDriver) && $recordDriver->getFountasPinnellLevel()}
		<div class="full-record-property full-record-fountas-pinnell row">
			<div class="result-label col-md-3">{translate text='Fountas & Pinnell' isPublicFacing=true}</div>
			<div class="col-md-9 result-value">
				{$recordDriver->getFountasPinnellLevel()|escape}
			</div>
		</div>
	{/if}

	{if !empty($notes)}
		<div role="heading" aria-level="2" style="margin: 20px 0 10px;">
			<span style="display: inline-block; font-size: 18px;">{translate text='Notes' isPublicFacing=true}</span>
		</div>
		{foreach from=$notes item=note name=loop}
			<div class="full-record-notes row">
				<div class="result-label col-sm-3">{translate text=$note.label isPublicFacing=true isMetadata=true}</div>
				<div class="col-sm-9 result-value">{$note.note}{if !empty($note.agr)} <span class="agrNote">({$note.agr})</span> {/if}</div>
			</div>
		{/foreach}
	{/if}
{/strip}
