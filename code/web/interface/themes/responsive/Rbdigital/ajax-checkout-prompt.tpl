{strip}
<form method="post" action="" id="checkoutPromptsForm" class="form">
	<div>
		<input type="hidden" name="id" value="{$id}">
		{if count($users) > 1} {* Linked Users contains the active user as well*}
			<div id='pickupLocationOptions' class="form-group">
				<label class="control-label" for="patronId">{translate text="Checkout to account"}: </label>
				<div class="controls">
					<select name="patronId" id="patronId" class="form-control">
						{foreach from=$users item=tmpUser}
							<option value="{$tmpUser->id}">{$tmpUser->displayName} - {$tmpUser->getHomeLibrarySystemName()}</option>
						{/foreach}
					</select>
				</div>
			</div>
		{/if}
	</div>
</form>
{/strip}