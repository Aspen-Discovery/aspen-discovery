{strip}
    <section class="well">
        <p class="alert alert-danger" id="minimalSelfRegError" style="display: none"></p>
        {if !empty($introText)}<p>{translate text=$introText isPublicFacing=true}</p>{/if}
    	<div id="selfRegistrationFormContainer">
    		{$minimalSelfRegForm}
    	</div>
        {if !empty($footerText)}<p>{translate text=$footerText isPublicFacing=true}</p>{/if}
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
