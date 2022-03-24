{strip}
	<div id="page-content" class="col-xs-12">

		<h1>{translate text='Forgotten Password Recovery' isPublicFacing=true}</h1>

		{if !empty($error)}
			<div class="alert alert-warning">{$error}</div>
		{else}
			<div class="alert alert-info">
				{if $pinValidationRules.onlyDigitsAllowed}
					{translate text="PINs must be between %1% and %2% digits." isPublicFacing=true 1=$pinValidationRules.minLength 2=$pinValidationRules.maxLength}
				{else}
					{translate text="PINs must be between %1% and %2% characters." isPublicFacing=true 1=$pinValidationRules.minLength 2=$pinValidationRules.maxLength}
				{/if}
			</div>
			<form id="passwordRecovery" method="POST" action="/MyAccount/PasswordRecovery" class="form-horizontal">
				{if $uniqueKey}
					<input type="hidden" name="uniqueKey" value="{$uniqueKey}">
				{/if}
				<div class="form-group">
					<div class="col-xs-4"><label for="pin1" class="control-label">{translate text='New Password' isPublicFacing=true}</label></div>
					<div class="col-xs-8">
						<input type="password" name="pin1" id="pin1" value="" minlength="{$pinValidationRules.minLength}" maxlength="{$pinValidationRules.maxLength}" class="form-control required {if $pinValidationRules.onlyDigitsAllowed}digits{/if}">
					</div>
					{if !empty($passwordNote)}
						<div class='propertyDescription'><em>{$passwordNote}</em></div>
					{/if}
				</div>
				<div class="form-group">
					<div class="col-xs-4"><label for="pin2" class="control-label">{translate text='Re-enter New Password' isPublicFacing=true}</label></div>
					<div class="col-xs-8">
						<input type="password" name="pin2" id="pin2" value="" minlength="{$pinValidationRules.minLength}" maxlength="{$pinValidationRules.maxLength}" class="form-control required {if $pinValidationRules.onlyDigitsAllowed}digits{/if}">
					</div>
				</div>
				<div class="form-group">
					<div class="col-xs-8 col-xs-offset-4">
						<input id="passwordRecoverySubmit" name="submit" class="btn btn-primary" type="submit" value="{translate text='Reset My Password' inAttribute=true isPublicFacing=true}">
					</div>
				</div>
			</form>
		{/if}
	</div>
{/strip}
<script type="text/javascript">
    {literal}
	$(function () {
		$("#passwordRecovery").validate({
			rules: {
				pin2: {
					equalTo: "#pin1"
				}
			}
		});
	});
    {/literal}
</script>
