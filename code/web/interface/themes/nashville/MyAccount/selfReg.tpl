{strip}
    <h1>{translate text='Register for a Library Card'}</h1>
    <div class="page">
        {if (isset($selfRegResult) && $selfRegResult.success)}
            <div id="selfRegSuccess" class="alert alert-success">
                {if $selfRegistrationSuccessMessage}
                    {translate text=$selfRegistrationSuccessMessage}
                {else}
                    {translate text='selfreg_success_nashville' defaultText='<p>Congratulations, you have successfully registered for a new library card.</p><p>Your library card number has been emailed to you. This gives you immediate access to our online streaming, download, and database content for 45 days.</p><p>To maintain access indefinitely, visit any <a href="https://library.nashville.org/locations">NPL branch</a> with photo ID and <a href="https://library.nashville.org/get-card#getting-a-card">proof of Davidson County residency</a>.</p>'}
                {/if}
            </div>
        {elseif (isset($selfRegResult) && $selfRegResult.success === false && isset($selfRegResult.message))}
            <div id="selfRegFail" class="alert alert-warning">{translate text=$selfRegResult.message}</div>
        {else}
            {img_assign filename='self_reg_banner.png' var=selfRegBanner}
            {if $selfRegBanner}
                <img src="{$selfRegBanner}" alt="Self Register for a new library card" class="img-responsive">
            {/if}

            <div id="selfRegDescription" class="alert alert-info">
                {if $selfRegistrationFormMessage}
                {translate text=$selfRegistrationFormMessage}
                {else}
                <p>Residents of Davidson County or the City of Goodlettsville may register for a digital access card. We will email you a card number that gives you immediate access to our online streaming, download, and database content for 45 days. To maintain access indefinitely, visit any <a href="https://library.nashville.org/locations">NPL branch</a> with photo ID and <a href="https://library.nashville.org/get-card#getting-a-card">proof of Davidson County residency</a>.</p>

                <p>By completing this form, you are agreeing to receive news and updates from Nashville Public Library and <a href="https://nplf.org">Nashville Public Library Foundation</a>.</p>

                <p>Requirements:</p>
                <ul>
                    <li>You must be age 13 or older</li>
                    <li>You must live in Davidson County or Goodlettsville</li>
                    <li>You must provide your email address</li>

                    {/if}
            </div>
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
            $('input.datePika').datepicker({
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
