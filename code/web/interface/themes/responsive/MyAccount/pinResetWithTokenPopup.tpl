{strip}
	<div class="alert alert-warning">{translate text="Your PIN has expired, enter a new PIN below." isPublicFacing=true}</div>
	<div class="alert alert-info">
		{if $pinValidationRules.onlyDigitsAllowed}
			{translate text="PINs must be between %1% and %2% digits." isPublicFacing=true 1=$pinValidationRules.minLength 2=$pinValidationRules.maxLength}
		{else}
			{translate text="PINs must be between %1% and %2% characters." isPublicFacing=true 1=$pinValidationRules.minLength 2=$pinValidationRules.maxLength}
		{/if}
	</div>

	<form method="post" role="form" action="/MyAccount/CompletePinReset" id="resetPin" class="form-horizontal">
		<input type='hidden' name='token' id='token' value='{$token}' />
		<div class="form-group">
			<div class="col-xs-4"><label for="pin1" class="control-label">{translate text='New PIN' translateParameters=true isPublicFacing=true}</label></div>
			<div class="col-xs-8">
				<input type="password" name="pin1" id="pin1" value="" size="{$pinValidationRules.minLength}" maxlength="{$pinValidationRules.maxLength}" class="form-control required {if $pinValidationRules.onlyDigitsAllowed}digits{/if}">
			</div>
		</div>
		<div class="form-group">
			<div class="col-xs-4"><label for="pin2" class="control-label">{translate text='Re-enter New PIN' translateParameters=true isPublicFacing=true}</label></div>
			<div class="col-xs-8">
					<input type="password" name="pin2" id="pin2" value="" size="{$pinValidationRules.minLength}" maxlength="{$pinValidationRules.maxLength}" class="form-control required {if $pinValidationRules.onlyDigitsAllowed}digits{/if}">
			</div>
		</div>
		{if !isset($showSubmitButton) || $showSubmitButton == true}
			<div class="form-group">
				<div class="col-xs-8 col-xs-offset-4">
					<button type="submit" name="update" class="btn btn-primary">{translate text="Update" isPublicFacing=true}</button>
				</div>
			</div>
		{/if}
	</form>

	<script type="text/javascript">
		$(function(){ldelim}{literal}
			$("#resetPin").validate({
				rules: {
					pin2: {
						equalTo: "#pin1"
					}
				}
			});
		{/literal}{rdelim});
	</script>
{/strip}