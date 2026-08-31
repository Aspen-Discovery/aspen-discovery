{strip}
	<div id="main-content">
		{if !empty($loggedIn)}
			{if !empty($profile->_web_note)}
				<div class="row">
					<div id="web_note" class="alert alert-info text-center col-xs-12">{$profile->_web_note}</div>
				</div>
			{/if}
			{if !empty($accountMessages)}
				{include file='systemMessages.tpl' messages=$accountMessages}
			{/if}
			{if !empty($ilsMessages)}
				{include file='ilsMessages.tpl' messages=$ilsMessages}
			{/if}

			<h1>{translate text='Reset %1%' 1=$passwordLabel translateParameters=true isPublicFacing=true}</h1>
			{if !empty($offline)}
				<div class="alert alert-warning"><strong>{translate text=$offlineMessage isPublicFacing=true}</strong></div>
			{else}
				{if !empty($profileUpdateErrors)}
					{foreach from=$profileUpdateErrors item=errorMsg}
						<div class="alert alert-danger">{$errorMsg}</div>
					{/foreach}
				{/if}
				{if !empty($profileUpdateMessage)}
					{foreach from=$profileUpdateMessage item=msg}
						<div class="alert alert-success">{$msg}</div>
					{/foreach}
				{/if}
				<div class="alert alert-info">
					{if !empty($pinValidationRules.requireStrongPassword)}
						{translate text="A strong password from 12 to 50 characters including at least one uppercase, lowercase, number, and special character (-_~!@#$%^&*.+)." isPublicFacing=true 1=$pinValidationRules.minLength 2=$pinValidationRules.maxLength}
					{elseif !empty($pinValidationRules.onlyDigitsAllowed)}
						{translate text="%3% must be between %1% and %2% digits." isPublicFacing=true translateParameters=true 1=$pinValidationRules.minLength 2=$pinValidationRules.maxLength 3=$passwordLabel}
					{else}
						{translate text="%3% must be between %1% and %2% characters." isPublicFacing=true translateParameters=true 1=$pinValidationRules.minLength 2=$pinValidationRules.maxLength 3=$passwordLabel}
					{/if}
				</div>

				{* Empty action attribute uses the page loaded. this keeps the selected user patronId in the parameters passed back to server *}
				<form action="" method="post" id="pinForm">
					<input type="hidden" name="updateScope" value="pin">
					<div class="form-group propertyRow">
						<label for="pin" class="control-label">{translate text='Old %1%' 1=$passwordLabel translateParameters=true isPublicFacing=true}</label>
						<input type="password" name="pin" id="pin" value="" size="{$pinValidationRules.minLength}" maxlength="60" class="form-control required {if !empty($pinValidationRules.onlyDigitsAllowed)}digits{/if}">
					</div>
					<div class="form-group propertyRow">
						<label for="pin1" class="control-label">{translate text='New %1%' 1=$passwordLabel translateParameters=true isPublicFacing=true}</label>
						<input type="password" name="pin1" id="pin1" value="" minlength="{$pinValidationRules.minLength}" maxlength="{$pinValidationRules.maxLength}" class="form-control required {if !empty($pinValidationRules.onlyDigitsAllowed)}digits{/if}">
					</div>
					<div class="form-group propertyRow">
						<label for="pin2" class="control-label">{translate text='Re-enter New %1%' 1=$passwordLabel translateParameters=true isPublicFacing=true}</label>
						<input type="password" name="pin2" id="pin2" value="" minlength="{$pinValidationRules.minLength}" maxlength="{$pinValidationRules.maxLength}" class="form-control required {if !empty($pinValidationRules.onlyDigitsAllowed)}digits{/if}">
					</div>
					<div class="form-group propertyRow">
						<button type="submit" name="update" class="btn btn-primary">{translate text="Update" isPublicFacing=true}</button>
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

				<script type="text/javascript">
					{* Initiate any checkbox with a data attribute set to data-switch=""  as a bootstrap switch *}
					{literal}
					$(function(){ $('input[type="checkbox"][data-switch]').bootstrapSwitch()});
					{/literal}
				</script>
			{/if}
		{else}
			<div class="page">
				{translate text="You must sign in to view this information." isPublicFacing=true}<a href='/MyAccount/Login' class="btn btn-primary">{translate text="Sign In" isPublicFacing=true}</a>
			</div>
		{/if}
	</div>
{/strip}
