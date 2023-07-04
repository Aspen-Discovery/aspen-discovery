{if !$smarty.cookies.cookieConsent}
    <div class="stripPopup">
        <div class="container">
            <div class="contentWrap">
                <span>We use cookies on this site to enhance your user experience.</span>
                <abbr>By clicking any link on this website, you are giving us consent to set and store cookies.</abbr>
            </div>
            <div class="btnWrap">
                <a href="#" id="consentAgree" class="button">Yes, I agree</a>
                <a href="#" id="consentDisagree" class="button">No, I want to find out more</a>
            </div>
        </div>
    </div>
    <link rel="stylesheet" href="/interface/themes/responsive/css/cookie-consent.css" />
    <script>cookiePolicyHTML='{$cookieStorageConsentHTML|escape:javascript|regex_replace:"/[\r\n]/" : " "}'</script>
    <script src="/interface/themes/responsive/js/aspen/cookieConsent.js"></script>
{/if}