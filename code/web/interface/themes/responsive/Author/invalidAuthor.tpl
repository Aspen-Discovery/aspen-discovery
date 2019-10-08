<h1>{translate text='Invalid Author'}</h1>

{if $authorName}
	<p class="alert alert-warning">Sorry, we could not find the author <strong>{$authorName}</strong> in our catalog.	Please try your search again.</p>
{else}
	<p class="alert alert-warning">No author was provided, please try your search again.</p>
{/if}
{if $materialRequestType == 1 }
	<p class="alert alert-info">
		Can't find what you are looking for? Try our <a href="{$path}/MaterialsRequest/NewRequest" onclick="return AspenDiscovery.Account.followLinkIfLoggedIn(this);">{'Materials Request'|translate} Service</a>.
	</p>
{elseif $materialRequestType == 2}
	<p class="alert alert-info">
		Can't find what you are looking for? Try our <a href="{$path}/MaterialsRequest/NewRequestIls" onclick="return AspenDiscovery.Account.followLinkIfLoggedIn(this);">{'Materials Request'|translate} Service</a>.
	</p>
{elseif $materialRequestType == 3}
	<p class="alert alert-info">
		Can't find what you are looking for? Try our <a href="{$externalMaterialsRequestUrl}">{'Materials Request'|translate} Service</a>.
	</p>
{/if}
