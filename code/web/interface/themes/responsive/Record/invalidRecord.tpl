<h1>{translate text='Invalid Record'}</h1>
<p class="alert alert-warning">
	{if empty($invalidWork)}
		{translate text='Sorry, we could not find a record with an id of <b>%1%</b> in our catalog.	Please try your search again.' 1=$id}
	{else}
		{if $invalidWork}
			{translate text='Sorry, this title (<b>%1%</b>) no longer exists in our catalog. Please try searching for other titles.' 1=$id}
		{else}
			{translate text='Sorry, we could not find a record with an id of <b>%1%</b> in our catalog, the record was not grouped properly. Please try your search again.' 1=$id}
		{/if}
	{/if}
</p>

{if $materialRequestType == 1 }
	<p class="alert alert-info">
		Can't find what you are looking for? Try our <a href="/MaterialsRequest/NewRequest" onclick="return AspenDiscovery.Account.followLinkIfLoggedIn(this);">{'Materials Request'|translate} Service</a>.
	</p>
{elseif $materialRequestType == 2}
	<p class="alert alert-info">
		Can't find what you are looking for? Try our <a href="/MaterialsRequest/NewRequestIls" onclick="return AspenDiscovery.Account.followLinkIfLoggedIn(this);">{'Materials Request'|translate} Service</a>.
	</p>
{elseif $materialRequestType == 3}
	<p class="alert alert-info">
		Can't find what you are looking for? Try our <a href="{$externalMaterialsRequestUrl}">{'Materials Request'|translate} Service</a>.
	</p>
{/if}
