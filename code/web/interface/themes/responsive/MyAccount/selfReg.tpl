{strip}
<h1>{translate text='Register for a Library Card'}</h1>
<div class="page">
	{if (isset($selfRegResult) && $selfRegResult.success)}
		<div id="selfRegSuccess" class="alert alert-success">
			{if $selfRegistrationSuccessMessage}
				{translate text=$selfRegistrationSuccessMessage}
			{else}
				{translate text='selfreg_success' defaultText='Congratulations, you have successfully registered for a new library card. You will have limited privileges initially.<br>	Please bring a valid ID to the library to receive a physical library card with full privileges.'}
			{/if}
		</div>
		<div class="alert alert-info">
			{if !empty($selfRegResult.barcode)}
				<p>Your library card number is <strong>{$selfRegResult.barcode}</strong></p>
			{/if}
			{if !empty($selfRegResult.username)}
				<p>Your username is <strong>{$selfRegResult.username}</strong></p>
			{/if}
			{if !empty($selfRegResult.password)}
				<p>Your initial password is <strong>{$selfRegResult.password}</strong></p>
			{/if}
			{if !empty($selfRegResult.message)}
				<p class="alert alert-warning">{$selfRegResult.message}</p>
			{/if}
		</div>
	{else}
		{img_assign filename='self_reg_banner.png' var=selfRegBanner}
		{if $selfRegBanner}
			<img src="{$selfRegBanner}" alt="Self Register for a new library card" class="img-responsive">
		{/if}

		<div id="selfRegDescription" class="alert alert-info">
			{if $selfRegistrationFormMessage}
				{translate text=$selfRegistrationFormMessage}
			{else}
				{translate text='selfreg_info' defaultText='This page allows you to register as a patron of our library online. You will have limited privileges initially.'}
			{/if}
		</div>
		{if (isset($selfRegResult))}
			<div id="selfRegFail" class="alert alert-warning">
				{if !empty($selfRegResult.message)}
					{translate text=$selfRegResult.message}
				{else}
					{translate text='selfreg_fail' defaultText='Sorry, we were unable to create a library card for you.<br>You may already have an account or there may be an error with the information you entered.<br>Please try again or visit the library in person (with a valid ID) so we can create a card for you.'}
				{/if}
			</div>
		{/if}
		{if $captchaMessage}
			<div id="selfRegFail" class="alert alert-warning">
			{$captchaMessage}
			</div>
		{/if}
		<div id="selfRegistrationFormContainer">
			{$selfRegForm}
		</div>
	{/if}
</div>
{/strip}
{if $promptForBirthDateInSelfReg}
<script type="text/javascript">
	{* #borrower_note is birthdate for anythink *}
	{* this is bootstrap datepicker, not jquery ui *}
	{literal}
	$(document).ready(function(){
		$('input.dateAspen').datepicker({
			format: "mm-dd-yyyy"
			,endDate: "+0d"
			,startView: 2
		});
	});
	{/literal}
	{* Pin Validation for CarlX, Sirsi *}
	{literal}
	if ($('#pin').length > 0 && $('#pin1').length > 0) {
		$("#objectEditor").validate({
			rules: {
				pin: {
					minlength: 4
				},
				pin1: {
					minlength: 4,
					equalTo: "#pin"
				}
			}
		});
	}
	{/literal}

</script>
{/if}