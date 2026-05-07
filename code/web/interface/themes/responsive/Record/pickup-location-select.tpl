{if count($pickupLocations) > 0}
	<div class="form-group">
		<label class="control-label" for="pickupBranch">{translate text="Pickup Location" isPublicFacing=true}</label>
		<select name="pickupBranch" id="pickupBranch" class="form-control">
			{foreach from=$pickupLocations item=location}
				{if is_string($location)}
					<option value="undefined">{$location}</option>
				{else}
					<option value="{$location->code}" {if !empty($preSelectedPickupBranch)}{if $location->code == $preSelectedPickupBranch}selected{/if}{else}{if $location->code == $user->getPickupLocationCode()}selected{/if}{/if}>{$location->displayName|escape}</option>
				{/if}
			{/foreach}
		</select>
	</div>
{/if}
