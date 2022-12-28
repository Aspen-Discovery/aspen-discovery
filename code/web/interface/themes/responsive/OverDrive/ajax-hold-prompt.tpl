{strip}
<form method="post" action="" id="overdriveHoldPromptsForm" class="form">
	<div>
		<input type="hidden" name="overdriveId" value="{$overDriveId}"/>
		{if count($overDriveUsers) > 1} {* Linked Users contains the active user as well*}
			<div id='pickupLocationOptions' class="form-group">
				<label class='control-label' for="patronId">{translate text="Place hold for account" isPublicFacing=true} </label>
				<div class='controls'>
					<select name="patronId" id="patronId" class="form-control">
						{foreach from=$overDriveUsers item=tmpUser}
							<option value="{$tmpUser->id}">{$tmpUser->displayName} - {$tmpUser->getHomeLibrarySystemName()}</option>
						{/foreach}
					</select>
				</div>
			</div>
		{else}
			<input type="hidden" name="patronId" id="patronId" value="{$patronId}">
		{/if}

		{if !empty($promptForEmail)}
			<div class="form-group">
				<label for="overdriveEmail" class="control-label">{translate text="Enter an email to be notified when the title is ready for you." isPublicFacing=true}</label>
				<input type="text" class="email form-control" name="overdriveEmail" id="overdriveEmail" value="{$overdriveEmail}" size="40" maxlength="250"/>
			</div>
			<div class="checkbox">
				<label for="promptForOverdriveEmail" class="control-label"><input type="checkbox" name="promptForOverdriveEmail" id="promptForOverdriveEmail"/> {translate text="Remember these settings" isPublicFacing=true}</label>
			</div>
		{else}
			<input type="hidden" name="overdriveEmail" value="{$overdriveEmail}"/>
		{/if}
	</div>
</form>
{/strip}