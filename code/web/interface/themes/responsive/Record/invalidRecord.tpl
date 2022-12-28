<h1>{translate text='Invalid Record' isPublicFacing=true}</h1>
<p class="alert alert-warning">
	{if empty($invalidWork)}
		{translate text='Sorry, we could not find a record with an id of <b>%1%</b> in our catalog.	Please try your search again.' 1=$id isPublicFacing=true}
	{else}
		{if !empty($invalidWork)}
			{translate text='Sorry, this title (<b>%1%</b>) no longer exists in our catalog. Please try searching for other titles.' 1=$id isPublicFacing=true}
		{else}
			{translate text='Sorry, we could not find a record with an id of <b>%1%</b> in our catalog, the record was not grouped properly. Please try your search again.' 1=$id isPublicFacing=true}
		{/if}
	{/if}
</p>

{if $materialRequestType == 1 && $displayMaterialsRequest }
	<p class="alert alert-info materialsRequestLink">
		{translate text="Can't find what you are looking for? Try our Materials Request Service." isPublicFacing=true} <a href="/MaterialsRequest/NewRequest" class="btn btn-sm btn-info" onclick="return AspenDiscovery.Account.followLinkIfLoggedIn(this);">{translate text='Submit Request' isPublicFacing=true}</a>
	</p>
{elseif $materialRequestType == 2 && $displayMaterialsRequest}
	<p class="alert alert-info materialsRequestLink">
		{translate text="Can't find what you are looking for? Try our Materials Request Service." isPublicFacing=true} <a href="/MaterialsRequest/NewRequestIls" class="btn btn-sm btn-info" onclick="return AspenDiscovery.Account.followLinkIfLoggedIn(this);">{translate text='Submit Request' isPublicFacing=true}</a>
	</p>
{elseif $materialRequestType == 3 && $displayMaterialsRequest}
	<p class="alert alert-info materialsRequestLink">
		{translate text="Can't find what you are looking for? Try our Materials Request Service." isPublicFacing=true} <a href="{$externalMaterialsRequestUrl}" class="btn btn-sm btn-info">{translate text='Submit Request' isPublicFacing=true}</a>
	</p>
{/if}
{if !empty($showStaffView)}
	<h2>{translate text="Staff View" isPublicFacing=true}</h2>
	<div id="staffViewPlaceHolder">{translate text='Loading Staff View.' isPublicFacing=true}</div>
	<script type="text/javascript">
		AspenDiscovery.Record.getStaffView('{$module}', '{$id}')
	</script>
{/if}