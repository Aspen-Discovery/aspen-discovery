<div id="page-content" class="row">
	<div id="main-content">
		<h2>{translate text='Invalid Record'}</h2>
			
		<p class="alert alert-warning">Sorry, we could not find a record with an id of {$id} in our catalog.	Please try your search again.</p>
		{if $enableMaterialsRequest}
			<p class="alert alert-info">
				Can't find what you are looking for? Try our <a href="{$path}/MaterialsRequest/NewRequest" onclick="return VuFind.Account.followLinkIfLoggedIn(this);">{'Materials Request'|translate} Service</a>.
			</p>
		{elseif $externalMaterialsRequestUrl}
			<p class="alert alert-info">
				Can't find what you are looking for? Try our <a href="{$externalMaterialsRequestUrl}">{'Materials Request'|translate} Service</a>.
			</p>
		{/if}
		
	</div>
</div>