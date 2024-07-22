{strip}
<form method="post" action="" id="checkoutPromptsForm" class="form">
	<div>
		<input type="hidden" id="id" name="id" value="{$id}">
		<input type="hidden" id="checkoutType" name="checkoutType" value="{$checkoutType}">
		<input type="hidden" id="useAlternateLibraryCard" name="useAlternateLibraryCard" value="{$useAlternateLibraryCard}">
		{if count($users) > 1} {* Linked Users contains the active user as well*}
			<div class="form-group">
				<label class="control-label" for="patronId">{translate text="Checkout to account" isPublicFacing=true} </label>
				<div class="controls">
					<select name="patronId" id="patronId" class="form-control">
						{foreach from=$users item=tmpUser}
							<option
									value="{$tmpUser->id}"
									data-valid-card="{in_array($tmpUser, $validCards)}"
							>
								{$tmpUser->displayName|escape} - {$tmpUser->getHomeLibrarySystemName()|escape}
							</option>
						{/foreach}
					</select>
				</div>
			</div>
		{/if}
	</div>
</form>
{/strip}