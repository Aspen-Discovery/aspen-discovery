<div id="page-content" class="row">
	<div id="main-content">
		<h2>{translate text='Invalid Record'}</h2>
			
		<p class="alert alert-warning">Sorry, we could not find a record with an id of {$id} in our catalog.	Please try your search again.</p>
		{if $materialRequestType == 1}
			<p class="alert alert-info">
				{translate text="Can't find what you are looking for?"} Try our <a href="/MaterialsRequest/NewRequest" onclick="return AspenDiscovery.Account.followLinkIfLoggedIn(this);">{'Materials Request Service'|translate}</a>.
			</p>
		{elseif $materialRequestType == 2}
			<p class="alert alert-info">
				{translate text="Can't find what you are looking for?"} Try our <a href="/MaterialsRequest/NewRequestIls" onclick="return AspenDiscovery.Account.followLinkIfLoggedIn(this);">{'Materials Request Service'|translate}</a>.
			</p>
		{elseif $materialRequestType == 3}
			<p class="alert alert-info">
				{translate text="Can't find what you are looking for?"} Try our <a href="{$externalMaterialsRequestUrl}">{'Materials Request Service'|translate}</a>.
			</p>
		{/if}
		
	</div>
</div>