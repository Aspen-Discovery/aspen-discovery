{strip}
	{if empty($showPublicationDetails) && $recordDriver->getPublicationDetails()}
		<div class="row">
			<div class="result-label col-md-3">{translate text='Published' isPublicFacing=true}</div>
			<div class="col-md-9 result-value">
				{implode subject=$recordDriver->getPublicationDetails() glue=", "}
			</div>
		</div>
	{/if}

	{if empty($showFormats)}
		<div class="row">
			<div class="result-label col-md-3">{translate text='Format' isPublicFacing=true}</div>
			<div class="col-md-9 result-value">
				{implode subject=$recordDriver->getFormats() glue=", " translate=true isPublicFacing=true}
			</div>
		</div>
	{/if}

	{if empty($showEditions) && $recordDriver->getEditions()}
		<div class="row">
			<div class="result-label col-md-3">{translate text='Edition' isPublicFacing=true}</div>
			<div class="col-md-9 result-value">
				{implode subject=$recordDriver->getEditions() glue=", "}
			</div>
		</div>
	{/if}

	<div class="row">
		<div class="result-label col-md-3">{translate text='Language' isPublicFacing=true}</div>
		<div class="col-md-9 result-value">
			{implode subject=$recordDriver->getLanguage() glue=", "}
		</div>
	</div>

	{if empty($showISBNs) && count($recordDriver->getISBNs()) > 0}
		<div class="row">
			<div class="result-label col-md-3">{translate text='ISBN' isPublicFacing=true}</div>
			<div class="col-md-9 result-value">
				{implode subject=$recordDriver->getISBNs() glue=", "}
			</div>
		</div>
	{/if}

	{if $recordDriver->getAcceleratedReaderData() != null}
		{assign var="arData" value=$recordDriver->getAcceleratedReaderData()}
		<div class="row">
			<div class="result-label col-md-3">{translate text='Accelerated Reader' isPublicFacing=true}</div>
			<div class="col-md-9 result-value">
				{if !empty($arData.interestLevel)}
					{$arData.interestLevel|escape}<br/>
				{/if}
				{translate text="Level %1%, %2% Points" 1=$arData.readingLevel|escape 2=$arData.pointValue|escape isPublicFacing=true}
			</div>
		</div>
	{/if}

	{if $recordDriver->getLexileCode()}
		<div class="row">
			<div class="result-label col-md-3">{translate text='Lexile code' isPublicFacing=true}</div>
			<div class="col-md-9 result-value">
				{$recordDriver->getLexileCode()|escape}
			</div>
		</div>
	{/if}

	{if $recordDriver->getLexileScore()}
		<div class="row">
			<div class="result-label col-md-3">{translate text='Lexile measure' isPublicFacing=true}</div>
			<div class="col-md-9 result-value">
				{$recordDriver->getLexileScore()|escape}
			</div>
		</div>
	{/if}

	{if $recordDriver->getFountasPinnellLevel()}
		<div class="row">
			<div class="result-label col-md-3">{translate text='Fountas & Pinnell' isPublicFacing=true}</div>
			<div class="col-md-9 result-value">
				{$recordDriver->getFountasPinnellLevel()|escape}
			</div>
		</div>
	{/if}

	{if $recordDriver->getSubjects()}
		<div class="row">
			<div class="result-label col-md-3">{translate text='Subjects' isPublicFacing=true}</div>
			<div class="col-md-9 result-value">
				{assign var="subjects" value=$recordDriver->getSubjects()}
				{foreach from=$subjects item=subject name=loop}
					<a href="/Search/Results?lookfor=%22{$subject|escape:"url"}%22&amp;searchIndex=Subject">{$subject|escape}</a>
					<br/>
				{/foreach}
			</div>
		</div>
	{/if}

{/strip}
