{if empty($smarty.cookies.cookieConsent)}
    <div class="stripPopup">
        <div class="cookieContainer">
            <div class="contentWrap">
                <span>{translate text="We use cookies on this site to enhance your user experience." isPublicFacing=true}</span>
                <abbr>{translate text="By clicking any link on this website, you are giving us consent to set and store cookies." isPublicFacing=true}<abbr>
            </div>
            <div class="btnWrap">
                <a onclick="AspenDiscovery.CookieConsent.cookieAgree();" href="#" id="consentAgree" class="button">{translate text="Yes, I agree" isPublicFacing=true}</a>
                <a onclick="AspenDiscovery.CookieConsent.cookieDisagree();" href="#" id="consentDisagree" class="button">{translate text="No, I want to find out more" isPublicFacing=true}</a>
            </div>
        </div>
    </div>
{/if}
