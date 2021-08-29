{strip}
	{* Details not shown in the Top/Main Section of the Record view should be shown here *}
	{if !$showPublicationDetails && $recordDriver->getPublicationDetails()}
		<div class="row">
			<div class="result-label col-sm-3">{translate text='Published' isPublicFacing=true}</div>
			<div class="col-sm-9 result-value">
				{implode subject=$recordDriver->getPublicationDetails() glue=", "}
			</div>
		</div>
	{/if}

	{if !$showFormats}
		<div class="row">
			<div class="result-label col-sm-3">{translate text='Format' isPublicFacing=true}</div>
			<div class="col-sm-9 result-value">
				{implode subject=$recordDriver->getFormats() glue=", " translate=true isPublicFacing=true}
			</div>
		</div>
	{/if}

	{if !$showEditions && $recordDriver->getEditions()}
		<div class="row">
			<div class="result-label col-sm-3">{translate text='Edition' isPublicFacing=true}</div>
			<div class="col-sm-9 result-value">
				{implode subject=$recordDriver->getEditions() glue=", "}
			</div>
		</div>
	{/if}

	<div class="row">
		<div class="result-label col-sm-3">{translate text='Language' isPublicFacing=true}</div>
		<div class="col-sm-9 result-value">
			{implode subject=$recordDriver->getLanguage() glue=", "}
		</div>
	</div>

	{if !$showISBNs && count($recordDriver->getISBNs()) > 0}
		<div class="row">
			<div class="result-label col-sm-3">{translate text='ISBN' isPublicFacing=true}</div>
			<div class="col-sm-9 result-value">
				{implode subject=$recordDriver->getISBNs() glue=", "}
			</div>
		</div>
	{/if}

	{if count($recordDriver->getUPCs()) > 0}
		<div class="row">
			<div class="result-label col-sm-3">{translate text='UPC' isPublicFacing=true}</div>
			<div class="col-sm-9 result-value">
				{implode subject=$recordDriver->getUPCs() glue=", "}
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

	{if $recordDriver->getLexileCode()}
		<div class="row">
			<div class="result-label col-sm-3">{translate text='Lexile code' isPublicFacing=true}</div>
			<div class="col-sm-9 result-value">
				{$recordDriver->getLexileCode()|escape}L
			</div>
		</div>
	{/if}

	{if $recordDriver->getLexileScore()}
		<div class="row">
			<div class="result-label col-sm-3">{translate text='Lexile measure' isPublicFacing=true}</div>
			<div class="col-sm-9 result-value">
				{$recordDriver->getLexileScore()|escape}
			</div>
		</div>
	{/if}

	{if $recordDriver->getFountasPinnellLevel()}
		<div class="row">
			<div class="result-label col-sm-3">{translate text='Fountas &amp; Pinnell' isPublicFacing=true}</div>
			<div class="col-sm-9 result-value">
				{$recordDriver->getFountasPinnellLevel()|escape}
			</div>
		</div>
	{/if}

	{if $notes}
		<h4>{translate text='Notes'}</h4>
		{foreach from=$notes item=note name=loop}
			<div class="row">
				<div class="result-label col-sm-3">{translate text=$note.label isPublicFacing=true isMetadata=true}</div>
				<div class="col-sm-9 result-value">{$note.note}</div>
			</div>
		{/foreach}
	{/if}
{/strip}