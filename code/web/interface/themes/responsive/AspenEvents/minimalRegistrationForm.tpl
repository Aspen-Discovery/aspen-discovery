{strip}
    <section class="well">
        <p>{translate text="Interested in joining our library Events? Use the quick registration form below to sign up and become a library member." isPublicFacing=true}</p>
    	<div id="selfRegistrationFormContainer">
    		{$minimalSelfRegForm}
    	</div>
        <p>{translate text="Already a member? Log in below to register to this event." isPublicFacing=true}</p>
    </section>

    <script type="text/javascript">
    	$(function () {
    		// Clear form data when navigating back so user info is not retained.
    		window.addEventListener('pageshow', function () {
    			document.querySelectorAll('form[id^="objectEditor"]').forEach(form => form.reset());
    		});
    		const $borrowerPassword2 = $("#borrower_password2");
    		if ($borrowerPassword2.length) {
    			$borrowerPassword2.attr('data-rule-equalTo', "#borrower_password");
    			$borrowerPassword2.attr('data-msg-equalTo', '{translate text="Passwords must match." isPublicFacing=true inAttribute=true}');
    		}
    	});
    </script>
{/strip}
