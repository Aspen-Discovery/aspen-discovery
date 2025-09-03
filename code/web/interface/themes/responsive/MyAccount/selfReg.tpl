{strip}
<h1>{translate text='Register for a Library Card' isPublicFacing=true}</h1>
<div class="page">
	{if (isset($selfRegResult) && $selfRegResult.success)}
		<div id="selfRegSuccess" class="alert alert-success">
			{if !empty($selfRegistrationSuccessMessage)}
				{$selfRegistrationSuccessMessage}
			{else}
				{translate text='Congratulations, you have successfully registered for a new library card. You will have limited privileges initially.<br>	Please bring a valid ID to the library to receive a physical library card with full privileges.' isPublicFacing=true}
			{/if}
		</div>
		<div id="selfRegAccountInfo" class="alert alert-info">
			{if !empty($selfRegResult.barcode)}
				<p id="selfRegBarcode">{translate text='Your library card number is <strong>%1%</strong>' 1=$selfRegResult.barcode isPublicFacing=true}</p>
			{/if}
			{if !empty($selfRegResult.username)}
				<p id="selfRegUsername">{translate text='Your username is <strong>%1%</strong>' 1=$selfRegResult.username isPublicFacing=true}</p>
			{/if}
			{if !empty($selfRegResult.password)}
				<p id="selfRegPassword">{translate text='Your initial password is <strong>%1%</strong>' 1=$selfRegResult.password isPublicFacing=true}</p>
			{/if}
			{if !empty($selfRegResult.message)}
				<p id="selfRegMessage" class="alert alert-warning">{$selfRegResult.message}</p>
			{/if}
			{if !empty($selfRegResult.requirePinReset)}
				<p id="selfRegResetPin">{translate text="To login to the catalog, you must first reset your PIN." isPublicFacing=true}  <a class="btn btn-default" href="/MyAccount/EmailResetPin">{translate text="Reset PIN/Password" isPublicFacing=true}</a> </p>
			{/if}
		</div>
	{elseif (isset($selfRegResult) && $selfRegResult.success === false)}
		{if (isset($selfRegResult))}
			<div id="selfRegFail" class="alert alert-warning">
				{if !empty($selfRegResult.message)}
					{$selfRegResult.message}
				{else}
					{translate text='Sorry, we were unable to create a library card for you.<br>You may already have an account or there may be an error with the information you entered.<br>Please try again or visit the library in person (with a valid ID) so we can create a card for you.' isPublicFacing=true}
				{/if}
			</div>
		{/if}
		{if !empty($captchaMessage)}
			<div id="selfRegFail" class="alert alert-warning">
				{$captchaMessage}
			</div>
		{/if}
	{else}
		{if !empty($addressMessage)}
			<div id="selfRegFail" class="alert alert-warning">
				{$addressMessage}
			</div>
		{/if}
		{if !empty($ageMessage)}
			<div id="selfRegFail" class="alert alert-warning">
				{$ageMessage}
			</div>
		{/if}
		{if !empty($emailMessage)}
			<div id="selfRegFail" class="alert alert-warning">
				{$emailMessage}
			</div>
		{/if}
		{if !empty($phoneMessage)}
			<div id="selfRegFail" class="alert alert-warning">
				{$phoneMessage}
			</div>
		{/if}
		{img_assign filename='self_reg_banner.png' var=selfRegBanner}
		{if !empty($selfRegBanner)}
			<img src="{$selfRegBanner}" alt="Self Register for a new library card" class="img-responsive">
		{/if}

		<div id="selfRegDescription" class="alert alert-info">
			{if !empty($selfRegistrationFormMessage)}
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
{if !empty($promptForBirthDateInSelfReg)}
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

<script type="text/javascript">
	$(function () {
		// Clear form data when navigating back so user info is not retained.
		window.addEventListener('pageshow', function () {
			document.querySelectorAll('form[id^="objectEditor"]').forEach(form => form.reset());
		});
		const $borrowPass2 = $("#borrower_password2");
		if ($borrowPass2.length) {
			$borrowPass2.attr('data-rule-equalTo', "#borrower_password");
			$borrowPass2.attr('data-msg-equalTo', '{translate text="Passwords must match." isPublicFacing=true inAttribute=true}');
		}

		// Prevent double-submission of self-registration form.
		let isSubmitting = false;
		$('form[id^="objectEditor"]').on('submit', (e) => {
			if (isSubmitting) return e.preventDefault();
			// Timeout to allow for jQuery validation to check first.
			setTimeout(() => {
				if ($(e.target).valid()) {
					isSubmitting = true;
					$(e.target).find('button[type="submit"][name="submit"]').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> {translate text="Processing..." isPublicFacing=true}');
				}
			}, 10);
		});
	});
</script>
