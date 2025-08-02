<form class="form" id="changeAllHoldsLocationForm" method="post">
	<div class="form-group">
		<label for="newPickupLocation" class="control-label">{translate text="New Pickup Location" isPublicFacing=true}</label>
		<select name="newPickupLocation" id="newPickupLocation" class="form-control" onchange="AspenDiscovery.updatePickupSublocations()">
			{foreach from=$pickupLocations item=location}
				<option value="{$location->code}">{$location->displayName}</option>
			{/foreach}
		</select>
	</div>
	<div class="form-group" id="pickupSubLocationSection" style="display: none">
		<label for="pickupSublocation" class="control-label">{translate text="Pickup Area" isPublicFacing=true}</label>
		<select name="pickupSublocation" id="pickupSublocation" class="form-control">
			<option value="">{translate text="No preference" isPublicFacing=true}</option>
		</select>
	</div>
	<input type="hidden" name="patronId" id="patronId" value="{$patronId}">
</form>

<script type="text/javascript">
	{literal}
	AspenDiscovery.updatePickupSublocations = function(){
		var pickupLocation = $("#newPickupLocation").val();
		var sublocations = subLocationsByLocation[pickupLocation];
		if (sublocations != undefined && sublocations.length > 0){
			$("#pickupSublocation").empty();
			$("#pickupSublocation").append(new Option("{/literal}{translate text="No preference" isPublicFacing=true}{literal}", ""));
			for (var i = 0; i < sublocations.length; i++){
				$("#pickupSublocation").append(new Option(sublocations[i].displayName, sublocations[i].id));
			}
			$("#pickupSubLocationSection").show();
		}else{
			$("#pickupSubLocationSection").hide();
		}
	};

	var subLocationsByLocation = {};
	{/literal}
	{foreach from=$pickupLocations item=location}
	{if is_object($location)}
	{assign var=locationId value=$location->locationId}
	{if !empty($pickupSublocations.$locationId)}
	subLocationsByLocation['{$location->code}'] = [
		{foreach from=$pickupSublocations.$locationId item=sublocation}
			{literal}{{/literal}
			id: '{$sublocation->id}',
			displayName: '{$sublocation->displayName}'
			{literal}}{/literal}{if !$sublocation@last},{/if}
		{/foreach}
	];
	{/if}
	{/if}
	{/foreach}

	$(document).ready(function(){
		AspenDiscovery.updatePickupSublocations();
	});
</script>
