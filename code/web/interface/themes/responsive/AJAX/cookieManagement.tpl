    {if $loggedIn}
        <script>
        cookieValues = {
            Essential: {$profile->userCookiePreferenceEssential},
            Analytics: {$profile->userCookiePreferenceAnalytics},
            UserAxis360: {$profile->userCookiePreferenceAxis360},
            UserEbscoEds: {$profile->userCookiePreferenceEbscoEds},
            UserEbscoHost: {$profile->userCookiePreferenceEbscoHost},
            UserSummon: {$profile->userCookiePreferenceSummon},
            UserEvents: {$profile->userCookiePreferenceEvents},
            UserHoopla: {$profile->userCookiePreferenceHoopla},
            UserOpenArchives: {$profile->userCookiePreferenceOpenArchives},
            UserOverdrive: {$profile->userCookiePreferenceOverdrive},
            UserPalaceProject: {$profile->userCookiePreferencePalaceProject},    
            UserSideLoad: {$profile->userCookiePreferenceSideLoad},
            UserCloudLibrary: {$profile->userCookiePreferenceCloudLibrary},
            UserWebsite: {$profile->userCookiePreferenceWebsite},
        };
        </script> 
       <div>
        <form method="post" name="cookieManagementPreferencesForm" id="cookieManagementPreferencesForm" class="form">
        <div>
        <label>
            <input type="checkbox" name="cookieEssential" id="cookieEssential" checked disabled> Essential Cookies
        </label>
    </div>
    <div>
        <label>
            <input type="checkbox" name="cookieAnalytics" id="cookieAnalytics"> Analytics Cookies
        </label>
    </div>
    <div>
        <label>
            <input type="checkbox" name="cookieUserAxis360" id="cookieUserAxis360"> Axis 360
        </label>
    </div>
    <div>
    <label>
        <input type="checkbox" name="cookieUserEbscoEds" id="cookieUserEbscoEds"> Ebsco Eds
    </label>
    </div>
    <div>
    <label>
        <input type="checkbox" name="cookieUserEbscoHost" id="cookieUserEbscoHost"> Ebsco Host
    </label>
    </div>
    <div>
    <label>
        <input type="checkbox" name="cookieUserSummon" id="cookieUserSummon"> Summon
    </label>
    </div>
    <div>
    <label>
        <input type="checkbox" name="cookieUserEvents" id="cookieUserEvents"> Events
    </label>
    </div>
    <div>
    <label>
        <input type="checkbox" name="cookieUserHoopla" id="cookieUserHoopla"> Hoopla
    </label>
    </div>
    <div>
    <label>
        <input type="checkbox" name="cookieUserOpenArchives" id="cookieUserOpenArchives"> Open Archives
    </label>
    </div>
    <div>
    <label>
    <input type="checkbox" name="cookieUserOverdrive" id="cookieUserOpenOverdrive"> Overdrive
    </label>
    </div>
    <div>
    <label>
    <input type="checkbox" name="cookieUserPalaceProject" id="cookieUserPalaceProject"> Palace Project
    </label>
    </div>
    <div>
    <label>
    <input type="checkbox" name="cookieUserSideLoad" id="cookieUserSideLoad"> Side Load
    </label>
    </div>
    <div>
    <label>
    <input type="checkbox" name="cookieUserCloudLibrary" id="cookieUserCloudLibrary"> Cloud Library
    </label>
    </div>
    <div>
    <label>
    <input type="checkbox" name="cookieUserWebsite" id="cookieUserWebsite"> Website
    </label>
    </div>
    <div>
            <button type="submit" class="btn btn-sm btn-default" onclick="return AspenDiscovery.CookieConsent.cookieManagementPreferences()">{translate text="Save Preferences" isPublicFacing=true}</button>
           </form>
       </div>
    {/if}
