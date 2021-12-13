{strip}
	{* Details not shown in the Top/Main Section of the Record view should be shown here *}
	{if $recordDriver && !$showPublicationDetails && $recordDriver->getPublicationDetails()}
		<div class="row">
			<div class="result-label col-xs-3">{translate text='Published' isPublicFacing=true}</div>
			<div class="col-xs-9 result-value">
				{implode subject=$recordDriver->getPublicationDetails() glue=", "}
			</div>
		</div>
	{/if}

	{if !$showFormats}
		<div class="row">
			<div class="result-label col-xs-3">{translate text='Format' isPublicFacing=true}</div>
			<div class="col-xs-9 result-value">
				{implode subject=$recordFormat glue=", "}
			</div>
		</div>
	{/if}

	{if $recordDriver && !$showEditions && $recordDriver->getEditions()}
		<div class="row">
			<div class="result-label col-xs-3">{translate text='Edition' isPublicFacing=true}</div>
			<div class="col-xs-9 result-value">
				{implode subject=$recordDriver->getEditions() glue=", "}
			</div>
		</div>
	{/if}

	{if !$showPhysicalDescriptions && $physicalDescriptions}
		<div class="row">
			<div class="result-label col-xs-3">{translate text='Physical Desc' isPublicFacing=true}</div>
			<div class="col-xs-9 result-value">
				{implode subject=$physicalDescriptions glue="<br/>"}
			</div>
		</div>
	{/if}

	{if $streetDate}
		<div class="row">
			<div class="result-label col-xs-3">{translate text='Street Date' isPublicFacing=true}</div>
			<div class="col-xs-9 result-value">
				{$streetDate|escape}
			</div>
		</div>
	{/if}

	<div class="row">
		<div class="result-label col-xs-3">{translate text='Language' isPublicFacing=true}</div>
		<div class="col-xs-9 result-value">
			{implode subject=$recordLanguage glue=", "}
		</div>
	</div>

	{if $recordDriver && !$showISBNs && count($recordDriver->getISBNs()) > 0}
		<div class="row">
			<div class="result-label col-xs-3">{translate text='ISBN' isPublicFacing=true}</div>
			<div class="col-xs-9 result-value">
				{implode subject=$recordDriver->getISBNs() glue=", "}
			</div>
		</div>
	{/if}

	{if $recordDriver && count($recordDriver->getISSNs()) > 0}
		<div class="row">
			<div class="result-label col-xs-3">{translate text='ISSN' isPublicFacing=true}</div>
			<div class="col-xs-9 result-value">
				{implode subject=$recordDriver->getISSNs() glue=", "}
			</div>
		</div>
	{/if}

	{if $recordDriver && count($recordDriver->getUPCs()) > 0}
		<div class="row">
			<div class="result-label col-xs-3">{translate text='UPC' isPublicFacing=true}</div>
			<div class="col-xs-9 result-value">
				{implode subject=$recordDriver->getUPCs() glue=", "}
			</div>
		</div>
	{/if}

	{if $recordDriver && $recordDriver->getAcceleratedReaderData() != null}
		{assign var="arData" value=$recordDriver->getAcceleratedReaderData()}
		<div class="row">
			<div class="result-label col-xs-3">{translate text='Accelerated Reader' isPublicFacing=true}</div>
			<div class="col-xs-9 result-value">
				{$arData.interestLevel|escape}<br/>
				{translate text="Level %1%, %2% Points" 1=$arData.readingLevel|escape 2=$arData.pointValue|escape isPublicFacing=true}
			</div>
		</div>
	{/if}

	{if $recordDriver && $recordDriver->getLexileCode()}
		<div class="row">
			<div class="result-label col-xs-3">{translate text='Lexile code' isPublicFacing=true}</div>
			<div class="col-xs-9 result-value">
				{$recordDriver->getLexileCode()|escape}
			</div>
		</div>
	{/if}

	{if $recordDriver && $recordDriver->getLexileScore()}
		<div class="row">
			<div class="result-label col-xs-3">{translate text='Lexile measure' isPublicFacing=true}</div>
			<div class="col-xs-9 result-value">
				{$recordDriver->getLexileScore()|escape}
			</div>
		</div>
	{/if}

	{if $recordDriver && $recordDriver->getFountasPinnellLevel()}
		<div class="row">
			<div class="result-label col-md-3">{translate text='Fountas & Pinnell' isPublicFacing=true}</div>
			<div class="col-md-9 result-value">
				{$recordDriver->getFountasPinnellLevel()|escape}
			</div>
		</div>
	{/if}

	{if $notes}
		<h4>{translate text='Notes' isPublicFacing=true}</h4>
		{foreach from=$notes item=note name=loop}
			<div class="row">
				<div class="result-label col-sm-3">{translate text=$note.label isPublicFacing=true isMetadata=true}</div>
				<div class="col-sm-9 result-value">{$note.note}</div>
			</div>
		{/foreach}
	{/if}
{/strip}