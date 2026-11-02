{strip}
	{if $recordDriver->getLanguage()}
		<div class="row">
			<div class="result-label col-md-3">{translate text='Language' isPublicFacing=true}</div>
			<div class="col-md-9 result-value">
				{translate text=$recordDriver->getLanguage() isPublicFacing=true isMetadata=true}
			</div>
		</div>
	{/if}

	{if $recordDriver->getPublishers()}
		<div class="row">
			<div class="result-label col-md-3">{translate text='Publisher' isPublicFacing=true}</div>
			<div class="col-md-9 result-value">
				{implode subject=$recordDriver->getPublishers() glue=", " escape=true}
			</div>
		</div>
	{/if}

	{if $recordDriver->getContributors()}
		<div class="row">
			<div class="result-label col-md-3">{translate text='Contributors' isPublicFacing=true}</div>
			<div class="col-md-9 result-value">
				{implode subject=$recordDriver->getContributors() glue=", " escape=true}
			</div>
		</div>
	{/if}
{/strip}
