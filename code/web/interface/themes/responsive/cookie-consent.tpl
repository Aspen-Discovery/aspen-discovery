{if $loggedIn && $profile->userCookiePreferenceEssential == 1}
    <script>
        cookieValues = {
                    Essential: {$profile->userCookiePreferenceEssential},
                    Analytics: {$profile->userCookiePreferenceAnalytics},
                };
        AspenDiscovery.CookieConsent.fetchUserCookie(encodeURIComponent(JSON.stringify(cookieValues)));
    </script>
{elseif empty($smarty.cookies.cookieConsent)}
    <div class="stripPopup">
        <div class="cookieContainer">
            <div class="contentWrap">
                <span>{translate text="We use cookies on this site to enhance your user experience." isPublicFacing=true}</span>
                <abbr>{translate text="For details about the cookies and technologies we use, see our <abbr style='display:inline-block'><u style='cursor:pointer;' onclick='AspenDiscovery.CookieConsent.cookieDisagree();'>cookie policy</u></abbr>. <br/> Using this banner will set a cookie on your device to remember your preferences." isPublicFacing=true}<abbr>
            </div>
            <div class="btnWrap">
                <a onclick="AspenDiscovery.CookieConsent.cookieAgree('all');" href="#" id="consentAgree" class="button">{translate text="Accept all cookies" isPublicFacing=true}</a>
                <a onclick="AspenDiscovery.CookieConsent.cookieAgree('essential');" href="#" id="consentDisagree" class="button">{translate text="Only accept essential cookies" isPublicFacing=true}</a>
            </div>
        </div>
    </div>
{/if}
