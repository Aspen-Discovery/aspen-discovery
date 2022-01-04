{strip}
	<div id="page-content" class="content">
		<div id="main-content">
			<div class="resultHead"><h1>{translate text='Reset My PIN' isPublicFacing=true}</h1></div>
			<div class="page">
				{if !empty($error)}
					<div class="alert alert-danger">{$error}</div>
				{/if}
				{if !empty($result) && $result.success}
					<div class="alert alert-success">{translate text='Your PIN has been reset and you have been logged in successfully.' isPublicFacing=true}</div>
					<div ><a href="/MyAccount/Home" class="btn btn-primary">{translate text='Continue to your account' isPublicFacing=true}</a> </div>
				{else}
					{if $tokenValid}
						<form method="post" role="form" action="/MyAccount/CompletePinReset">
							<input type='hidden' name='token' id='token' value='{$token}' />
							<div class="form-group">
								<div class="col-xs-4"><label for="pin1" class="control-label">{translate text='New %1%' 1=$passwordLabel translateParameters=true isPublicFacing=true}</label></div>
								<div class="col-xs-8">
									<input type="password" name="pin1" id="pin1" value="" size="{$pinValidationRules.minLength}" maxlength="{$pinValidationRules.maxLength}" class="form-control required {if $pinValidationRules.onlyDigitsAllowed}digits{/if}">
								</div>
							</div>
							<div class="form-group">
								<div class="col-xs-4"><label for="pin2" class="control-label">{translate text='Re-enter New %1%' 1=$passwordLabel translateParameters=true isPublicFacing=true}</label></div>
								<div class="col-xs-8">
										<input type="password" name="pin2" id="pin2" value="" size="{$pinValidationRules.minLength}" maxlength="{$pinValidationRules.maxLength}" class="form-control required {if $pinValidationRules.onlyDigitsAllowed}digits{/if}">
								</div>
							</div>
							<div class="form-group">
								<div class="col-xs-8 col-xs-offset-4">
									<button type="submit" name="update" class="btn btn-primary">{translate text="Update" isPublicFacing=true}</button>
								</div>
							</div>
							<script type="text/javascript">
								{* input classes  'required', 'digits' are validation rules for the validation plugin *}
								{literal}
								$("#pinForm").validate({
									rules: {
										pin2: {
											equalTo: "#pin1"
										}
									}
								});
								{/literal}
							</script>
						</form>
					{else}
						<a href="/MyAccount/InitiateResetPin" class="btn btn-primary">{translate text="Try Again" isPublicFacing=true}</a>
					{/if}
				{/if}
			</div>
		</div>
	</div>
{/strip}