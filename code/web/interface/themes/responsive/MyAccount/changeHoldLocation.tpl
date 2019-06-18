{strip}
	<form class="form" role="form">
		<input type="hidden" name="holdId" value="{$holdId}" id="holdId"/>
		<input type="hidden" name="patronId" value="{$patronId}" id="patronId"/>
		<div class="rateTitle form-group">
			<label for="newPickupLocation">{translate text="Select a new location to pickup your hold"}</label>
			<select name="newPickupLocation" id="newPickupLocation" class="form-control">
				{if count($pickupLocations) > 0}
					{foreach from=$pickupLocations item=location key=locationCode}
						<option value="{$locationCode}">{$location}</option>
					{/foreach}
				{else}
					<option>placeholder</option>
				{/if}
			</select>
		</div>
	</form>
{/strip}