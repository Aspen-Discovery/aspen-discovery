{strip}
	<form class="form" role="form">
		<input type="hidden" name="holdId" value="{$holdId}" id="holdId"/>
		<input type="hidden" name="patronId" value="{$patronId}" id="patronId"/>
		<input type="hidden" name="currentLocation" value="{$currentLocation}" id="currentLocation"/>
		<div class="rateTitle form-group">
			<label for="newPickupLocation">{translate text="Select a new location to pickup your hold"}</label>
			<select name="newPickupLocation" id="newPickupLocation" class="form-control">
				{if count($pickupLocations) > 0}
					{foreach from=$pickupLocations item=location key=locationCode}
						<option value="{$location->code}" {if is_object($location) && $location->getSelected()}selected="selected"{/if}>{$location->displayName}</option>
					{/foreach}
				{else}
					<option>placeholder</option>
				{/if}
			</select>
		</div>
	</form>
{/strip}