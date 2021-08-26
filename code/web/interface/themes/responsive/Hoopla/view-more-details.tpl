{strip}
	{* Details not shown in the Top/Main Section of the Record view should be shown here *}
	{if !$showPublicationDetails && $recordDriver->getPublicationDetails()}
		<div class="row">
			<div class="result-label col-md-3">{translate text='Published'}</div>
			<div class="col-md-9 result-value">
				{implode subject=$recordDriver->getPublicationDetails() glue=", "}
			</div>
		</div>
	{/if}


	{if !$showPhysicalDespriptions && $recordDriver->getPhysicalDescriptions()}
		<div class="row">
			<div class="result-label col-md-3">{translate text='Physical Description'}</div>
			<div class="col-md-9 result-value">
				{implode subject=$recordDriver->getPhysicalDescriptions() glue=", "}
			</div>
		</div>
	{/if}

	{if !$showFormats}
		<div class="row">
			<div class="result-label col-md-3">{translate text='Format'}</div>
			<div class="col-md-9 result-value">
				{implode subject=$recordDriver->getFormats() glue=", "}
			</div>
		</div>
	{/if}

	{if !$showEditions && $recordDriver->getEditions()}
		<div class="row">
			<div class="result-label col-md-3">{translate text='Edition'}</div>
			<div class="col-md-9 result-value">
				{implode subject=$recordDriver->getEditions() glue=", "}
			</div>
		</div>
	{/if}

	{if $recordDriver->getLanguage()}
		<div class="row">
			<div class="result-label col-xs-3">{translate text='Language'}</div>
			<div class="col-xs-9 result-value">
				{$recordDriver->getLanguage()}
			</div>
		</div>
	{/if}

	{if !$showISBNs && count($recordDriver->getISBNs()) > 0}
		<div class="row">
			<div class="result-label col-xs-3">{translate text='ISBN'}</div>
			<div class="col-xs-9 result-value">
				{implode subject=$recordDriver->getISBNs() glue=", "}
			</div>
		</div>
	{/if}

	{if count($recordDriver->getUPCs()) > 0}
		<div class="row">
			<div class="result-label col-xs-3">{translate text='UPC'}</div>
			<div class="col-xs-9 result-value">
				{implode subject=$recordDriver->getUPCs() glue=", "}
			</div>
		</div>
	{/if}

	{if $recordDriver->getAcceleratedReaderData() != null}
		{assign var="arData" value=$recordDriver->getAcceleratedReaderData()}
		<div class="row">
			<div class="result-label col-xs-3">{translate text='Accelerated Reader'}</div>
			<div class="col-xs-9 result-value">
				{$arData.interestLevel|escape}<br/>
				{translate text="Level %1%, %2% Points" 1=$arData.readingLevel|escape 2=$arData.pointValue|escape isPublicFacing=true}
			</div>
		</div>
	{/if}

	{if $recordDriver->getLexileCode()}
		<div class="row">
			<div class="result-label col-xs-3">{translate text='Lexile code'}</div>
			<div class="col-xs-9 result-value">
				{$recordDriver->getLexileCode()|escape}
			</div>
		</div>
	{/if}

	{if $recordDriver->getLexileScore()}
		<div class="row">
			<div class="result-label col-xs-3">{translate text='Lexile measure'}</div>
			<div class="col-xs-9 result-value">
				{$recordDriver->getLexileScore()|escape}
			</div>
		</div>
	{/if}

	{if $recordDriver->getFountasPinnellLevel()}
		<div class="row">
			<div class="result-label col-xs-3">{translate text='Fountas &amp; Pinnell'}</div>
			<div class="col-xs-9 result-value">
				{$recordDriver->getFountasPinnellLevel()|escape}
			</div>
		</div>
	{/if}

	{if $notes}
		<h4>{translate text='Notes'}</h4>
		{foreach from=$notes item=note name=loop}
			<div class="row">
				<div class="result-label col-xs-3">{$note.label|translate}</div>
				<div class="col-xs-9 result-value">{$note.note}</div>
			</div>
		{/foreach}
	{/if}
{/strip}