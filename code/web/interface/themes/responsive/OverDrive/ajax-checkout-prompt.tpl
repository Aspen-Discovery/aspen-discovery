{strip}
<form method="post" action="" id="overdriveCheckoutPromptsForm" class="form">
	<div>
		<input type="hidden" name="overdriveId" value="{$overDriveId}">
		{if count($overDriveUsers) > 1} {* Linked Users contains the active user as well*}
			<div id='pickupLocationOptions' class="form-group">
				<label class="control-label" for="patronId">{translate text="Checkout to account" isPublicFacing=true} </label>
				<div class="controls">
					<select name="patronId" id="patronId" class="form-control">
						{foreach from=$overDriveUsers item=tmpUser}
							<option value="{$tmpUser->id}">{$tmpUser->displayName} - {$tmpUser->getHomeLibrarySystemName()}</option>
						{/foreach}
					</select>
				</div>
			</div>
		{/if}
	</div>
</form>
{/strip}