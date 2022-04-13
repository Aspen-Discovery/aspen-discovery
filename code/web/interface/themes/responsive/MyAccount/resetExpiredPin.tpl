<div>
	<div class="alert alert-warning">{translate text="Your PIN has expired, enter a new PIN below." isPublicFacing=true}</div>
	<div class="alert alert-info">
		{if $pinValidationRules.onlyDigitsAllowed}
			{translate text="PINs must be between %1% and %2% digits." isPublicFacing=true 1=$pinValidationRules.minLength 2=$pinValidationRules.maxLength}
		{else}
			{translate text="PINs must be between %1% and %2% characters." isPublicFacing=true 1=$pinValidationRules.minLength 2=$pinValidationRules.maxLength}
		{/if}
	</div>

	<form id="resetPin" method="POST" action="/MyAccount/ResetPin" class="form-horizontal">
		{if $resetToken}
			<input type="hidden" name="resetToken" value="{$resetToken}">
		{/if}
		{if $userID}
			<input type="hidden" name="uid" value="{$userID}">
		{/if}
		<div class="form-group">
			<div class="col-xs-4">
				<label for="pin1" class="control-label">{translate text='New PIN' isPublicFacing=true}</label>
			</div>
			<div class="col-xs-8">
				<input type="password" name="pin1" id="pin1" value="" minlength="{$pinValidationRules.minLength}"
					   maxlength="{$pinValidationRules.maxLength}"
					   class="form-control required {if $pinValidationRules.onlyDigitsAllowed}digits{/if}">
			</div>
		</div>
		<div class="form-group">
			<div class="col-xs-4">
				<label for="pin2" class="control-label">{translate text='Re-enter New PIN' isPublicFacing=true}</label>
			</div>
			<div class="col-xs-8">
				<input type="password" name="pin2" id="pin2" value="" minlength="{$pinValidationRules.minLength}"
					   maxlength="{$pinValidationRules.maxLength}"
					   class="form-control required {if $pinValidationRules.onlyDigitsAllowed}digits{/if}">
			</div>
		</div>
		{if !isset($showSubmitButton) || $showSubmitButton == true}
			<div class="form-group">
				<div class="col-xs-8 col-xs-offset-4">
					<input id="resetPinSubmit" name="submit" class="btn btn-primary" type="submit"
						   value="{translate text="Reset My PIN" isPublicFacing=true}">
				</div>
			</div>
		{/if}
	</form>

	<script type="text/javascript">
		$(function(){ldelim}
			$("#resetPin").validate();
		{rdelim});
	</script>
</div>