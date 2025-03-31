{strip}
	<div id="page-content" class="col-xs-12">

		<h1>{translate text='Reset My PIN' isPublicFacing=true}</h1>
		<div class="alert alert-info">
			{if !empty($pinValidationRules.onlyDigitsAllowed)}
				{translate text="PINs must be between %1% and %2% digits." isPublicFacing=true 1=$pinValidationRules.minLength 2=$pinValidationRules.maxLength}
			{else}
				{translate text="PINs must be between %1% and %2% characters." isPublicFacing=true 1=$pinValidationRules.minLength 2=$pinValidationRules.maxLength}
			{/if}
		</div>

		<form id="resetPin" method="POST" action="/MyAccount/ResetPin">
			{if !empty($resetToken)}
				<input type="hidden" name="resetToken" value="{$resetToken}">
			{/if}
			{if !empty($userID)}
				<input type="hidden" name="uid" value="{$userID}">
			{/if}
			<div class="form-group propertyRow">
				<label for="pin1" class="control-label">{translate text='New PIN' isPublicFacing=true}</label>
				<input type="password" name="pin1" id="pin1" value="" minlength="{$pinValidationRules.minLength}" maxlength="{$pinValidationRules.maxLength}" class="form-control required {if !empty($pinValidationRules.onlyDigitsAllowed)}digits{/if}">
			</div>
			<div class="form-group propertyRow">
				<label for="pin2" class="control-label">{translate text='Re-enter New PIN' isPublicFacing=true}</label>
				<input type="password" name="pin2" id="pin2" value="" minlength="{$pinValidationRules.minLength}" maxlength="{$pinValidationRules.maxLength}" class="form-control required {if !empty($pinValidationRules.onlyDigitsAllowed)}digits{/if}">
			</div>
			<div class="form-group propertyRow">
				<button type="submit" id="resetPinSubmit" name="submit" class="btn btn-primary">
					{translate text="Reset My PIN" isPublicFacing=true}
				</button>
			</div>
		</form>
	</div>
{/strip}
<script type="text/javascript">
	{literal}
	$(function () {
		$("#resetPin").validate({
			rules: {
				pin2: {
					equalTo: "#pin1"
				}
			}
		});
	});
	{/literal}
</script>
