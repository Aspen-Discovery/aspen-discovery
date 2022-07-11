{strip}
<form method="post" action="" id="holdPromptsForm" class="form">
	<div>
		<input type="hidden" name="id" id="id" value="{$id}"/>
		{if count($users) > 1} {* Linked Users contains the active user as well*}
			<div id='pickupLocationOptions' class="form-group">
				<label class='control-label' for="patronId">{translate text="Place hold for account" isPublicFacing=true} </label>
				<div class='controls'>
					<select name="patronId" id="patronId" class="form-control">
						{foreach from=$users item=tmpUser}
							<option value="{$tmpUser->id}">{$tmpUser->displayName} - {$tmpUser->getHomeLibrarySystemName()}</option>
						{/foreach}
					</select>
				</div>
			</div>
		{else}
			<input type="hidden" name="patronId" id="patronId" value="{$patronId}">
		{/if}

		{if $promptForEmail}
            <div class="form-group">
                <label for="axis360Email" class="control-label">{translate text="Enter an email to be notified when the title is ready for you." isPublicFacing=true}</label>
                <input type="text" class="email form-control required" name="axis360Email" id="axis360Email" value="{$axis360Email}" size="40" maxlength="250" required/>
            </div>
            <div class="checkbox">
                <label for="promptForAxis360Email" class="control-label"><input type="checkbox" name="promptForAxis360Email" id="promptForAxis360Email"/> {translate text="Remember these settings" isPublicFacing=true}</label>
            </div>
        {else}
            <input type="hidden" name="axis360Email" value="{$axis360Email}"/>
        {/if}
	</div>
</form>
<script type="text/javascript">
	$("#holdPromptsForm").validate();
</script>
{/strip}