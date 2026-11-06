{strip}
<form method="post" action="" id="borrowboxCheckoutPromptsForm" class="form">
	<div>
		<input type="hidden" name="borrowboxId" id="borrowboxId" value="{$borrowboxId}">
		{if count($borrowBoxUsers) > 1}
			<div class="form-group">
				<label class="control-label" for="patronId">{translate text="Checkout to account" isPublicFacing=true} </label>
				<div class="controls">
					<select name="patronId" id="patronId" class="form-control">
						{foreach from=$borrowBoxUsers item=tmpUser}
							<option value="{$tmpUser->id}">{$tmpUser->displayName|escape} - {$tmpUser->getHomeLibrarySystemName()|escape}</option>
						{/foreach}
					</select>
				</div>
			</div>
		{/if}
	</div>
</form>
{/strip}
