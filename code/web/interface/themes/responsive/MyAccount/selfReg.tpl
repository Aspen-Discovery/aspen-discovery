{strip}
<h1>{translate text='Register for a Library Card' isPublicFacing=true}</h1>
<div class="page">
	{if (isset($selfRegResult) && $selfRegResult.success)}
		<div id="selfRegSuccess" class="alert alert-success">
			{if $selfRegistrationSuccessMessage}
				{translate text=$selfRegistrationSuccessMessage}
			{else}
				{translate text='Congratulations, you have successfully registered for a new library card. You will have limited privileges initially.<br>	Please bring a valid ID to the library to receive a physical library card with full privileges.' isPublicFacing=true}
			{/if}
		</div>
		<div id="selfRegAccountInfo" class="alert alert-info">
			{if !empty($selfRegResult.barcode)}
				<p>{translate text='Your library card number is <strong>%1%</strong>' 1=$selfRegResult.barcode isPublicFacing=true}</p>
			{/if}
			{if !empty($selfRegResult.username)}
				<p>{translate text='Your username is <strong>%1%</strong>' 1=$selfRegResult.username isPublicFacing=true}</p>
			{/if}
			{if !empty($selfRegResult.password)}
				<p>{translate text='Your initial password is <strong>%1%</strong>' 1=$selfRegResult.password isPublicFacing=true}</p>
			{/if}
			{if !empty($selfRegResult.message)}
				<p class="alert alert-warning">{$selfRegResult.message}</p>
			{/if}
		</div>
	{elseif (isset($selfRegResult) && $selfRegResult.success === false)}
		{if (isset($selfRegResult))}
			<div id="selfRegFail" class="alert alert-warning">
				{if !empty($selfRegResult.message)}
					{translate text=$selfRegResult.message}
				{else}
					{translate text='Sorry, we were unable to create a library card for you.<br>You may already have an account or there may be an error with the information you entered.<br>Please try again or visit the library in person (with a valid ID) so we can create a card for you.' isPublicFacing=true}
				{/if}
			</div>
		{/if}
		{if $captchaMessage}
			<div id="selfRegFail" class="alert alert-warning">
				{$captchaMessage}
			</div>
		{/if}

	{else}
		{img_assign filename='self_reg_banner.png' var=selfRegBanner}
		{if $selfRegBanner}
			<img src="{$selfRegBanner}" alt="Self Register for a new library card" class="img-responsive">
		{/if}

		<div id="selfRegDescription" class="alert alert-info">
			{if $selfRegistrationFormMessage}
				{translate text=$selfRegistrationFormMessage isPublicFacing=true isAdminEnteredData=true}
			{else}
				{translate text='This page allows you to register as a patron of our library online. You will have limited privileges initially.' isPublicFacing=true}
			{/if}
		</div>
		<div id="selfRegistrationFormContainer">
			{$selfRegForm}
		</div>
	{/if}
</div>
{/strip}
{if $promptForBirthDateInSelfReg}
<script type="text/javascript">
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