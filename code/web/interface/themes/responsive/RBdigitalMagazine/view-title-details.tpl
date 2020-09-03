{strip}
	{* Display more information about the title*}

	{if $showPublicationDetails && $recordDriver->getPublicationDetails()}
		<div class="row">
			<div class="result-label col-md-3">{translate text='Published'}</div>
			<div class="col-md-9 result-value">
				{implode subject=$recordDriver->getPublicationDetails() glue=", "}
			</div>
		</div>
	{/if}

	{if $showFormats}
		<div class="row">
			<div class="result-label col-md-3">{translate text='Format'}</div>
			<div class="col-md-9 result-value">
				{implode subject=$recordDriver->getFormats() glue=", "}
			</div>
		</div>
	{/if}
	
	{include file="GroupedWork/relatedLists.tpl"}

	<div class="row">
		<div class="result-label col-md-3">{translate text='Status'}</div>
		<div class="col-md-9 result-value result-value-bold statusValue {$holdingsSummary.class}" id="statusValue">{$holdingsSummary.status|escape}</div>
	</div>
{/strip}
