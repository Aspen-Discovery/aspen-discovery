<div id="page-content" class="row">
	<div id="main-content">
		<h2>{translate text='Invalid Record' isPublicFacing=true}</h2>
			
		<p class="alert alert-warning">{translate text="Sorry, we could not find a record with an id of <b>%1%</b> in our catalog.Please try your search again." 1=$id isPublicFacing=true}</p>
		{if $materialRequestType == 1 }
			<p class="alert alert-info">
				{translate text="Can't find what you are looking for? Try our Materials Request Service." isPublicFacing=true} <a href="/MaterialsRequest/NewRequest" class="btn btn-sm btn-info" onclick="return AspenDiscovery.Account.followLinkIfLoggedIn(this);">{translate text='Submit Request' isPublicFacing=true}</a>
			</p>
		{elseif $materialRequestType == 2}
			<p class="alert alert-info">
				{translate text="Can't find what you are looking for? Try our Materials Request Service." isPublicFacing=true} <a href="/MaterialsRequest/NewRequestIls" class="btn btn-sm btn-info" onclick="return AspenDiscovery.Account.followLinkIfLoggedIn(this);">{translate text='Submit Request' isPublicFacing=true}</a>
			</p>
		{elseif $materialRequestType == 3}
			<p class="alert alert-info">
				{translate text="Can't find what you are looking for? Try our Materials Request Service." isPublicFacing=true} <a href="{$externalMaterialsRequestUrl}" class="btn btn-sm btn-info">{translate text='Submit Request' isPublicFacing=true}</a>
			</p>
		{/if}
		
	</div>
</div>