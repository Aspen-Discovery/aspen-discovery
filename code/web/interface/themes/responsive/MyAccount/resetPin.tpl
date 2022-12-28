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

		<form id="resetPin" method="POST" action="/MyAccount/ResetPin" class="form-horizontal">
			{if !empty($resetToken)}
				<input type="hidden" name="resetToken" value="{$resetToken}">
			{/if}
			{if !empty($userID)}
				<input type="hidden" name="uid" value="{$userID}">
			{/if}
			<div class="form-group">
				<div class="col-xs-4"><label for="pin1" class="control-label">{translate text='New PIN' isPublicFacing=true}</label></div>
				<div class="col-xs-8">
					<input type="password" name="pin1" id="pin1" value="" minlength="{$pinValidationRules.minLength}" maxlength="{$pinValidationRules.maxLength}" class="form-control required {if !empty($pinValidationRules.onlyDigitsAllowed)}digits{/if}">
				</div>
			</div>
			<div class="form-group">
				<div class="col-xs-4"><label for="pin2" class="control-label">{translate text='Re-enter New PIN' isPublicFacing=true}</label></div>
				<div class="col-xs-8">
					<input type="password" name="pin2" id="pin2" value="" minlength="{$pinValidationRules.minLength}" maxlength="{$pinValidationRules.maxLength}" class="form-control required {if !empty($pinValidationRules.onlyDigitsAllowed)}digits{/if}">
				</div>
			</div>
			<div class="form-group">
				<div class="col-xs-8 col-xs-offset-4">
					<input id="resetPinSubmit" name="submit" class="btn btn-primary" type="submit" value="{translate text="Reset My Pin" isPublicFacing=true}">
				</div>
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
