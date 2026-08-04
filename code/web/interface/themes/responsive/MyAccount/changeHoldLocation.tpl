{strip}
	<form class="form" role="form">
		<input type="hidden" name="holdId" value="{$holdId}" id="holdId"/>
		<input type="hidden" name="patronId" value="{$patronId}" id="patronId"/>
		<input type="hidden" name="currentLocation" value="{$currentLocation}" id="currentLocation"/>
		<input type="hidden" name="currentSublocation" value="{$currentSublocation}" id="currentSublocation"/>
		<div class="rateTitle form-group">
			<label for="newPickupLocation">{translate text="Select a new branch to pickup your hold" isPublicFacing=true}</label>
			<select name="newPickupLocation" id="newPickupLocation" class="form-control" onchange="AspenDiscovery.Account.generateChangeSublocationSelect();">
				{if count($pickupLocations) > 0}
					{foreach from=$pickupLocations item=location key=locationCode}
						{if $location->code}
							<option value="{$location->code}" {if is_object($location) && ($location->locationId == $currentLocation)}selected="selected"{/if}>{$location->displayName|escape}</option>
						{/if}
					{/foreach}
				{else}
					<option>{translate text="placeholder" isPublicFacing=true inAttribute=true}</option>
				{/if}
			</select>
		</div>
		<div class="rateTitle form-group">
			<div id="pickupSublocationOptions" style="margin-top:15px">
				<div id="sublocationSelectPlaceHolder"></div>
			</div>
		</div>
		<script>
			AspenDiscovery.Account.generateChangeSublocationSelect({$currentSublocation});
		</script>
	</form>
{/strip}
