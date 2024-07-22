{strip}
<form method="post" action="" id="holdPromptsForm" class="form">
	<div>
		<input type="hidden" name="id" id="id" value="{$id}"/>
		<input type="hidden" id="useAlternateLibraryCard" name="useAlternateLibraryCard" value="{$useAlternateLibraryCard}">
		{if count($users) > 1} {* Linked Users contains the active user as well*}
			<div id='pickupLocationOptions' class="form-group">
				<label class='control-label' for="patronId">{translate text="Place hold for account" isPublicFacing=true} </label>
				<div class='controls'>
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
		{else}
			<input type="hidden" name="patronId" id="patronId" value="{$patronId}">
		{/if}
	</div>
</form>
{/strip}