  {strip}
       <div>
        <form method="post" name="cookieManagementPreferencesForm" id="cookieManagementPreferencesForm" class="form">
        <div>
        <label>
            <input type="checkbox" name="cookieEssential" checked disabled> Essential Cookies
        </label>
    </div>
    <div>
        <label>
            <input type="checkbox" name="cookieAnalytics"> Analytics Cookies
        </label>
    </div>
    <div>
        <label>
            <input type="checkbox" name="cookieUserAxis360" id="cookieUserAxis360"> Axis 360
        </label>
    </div>
            {* <button type="submit" class="btn btn-sm btn-default" onclick="return AspenDiscovery.CookieConsent.cookieManagementPreferences()" id="cookieConsentManage">{translate text="Save Preferences" isPublicFacing=true}</button> *}
            <button type="submit" class="btn btn-sm btn-default" onclick="return AspenDiscovery.CookieConsent.cookieManagementPreferences()">{translate text="Save Preferences" isPublicFacing=true}</button>
           </form>
       </div>
        {/strip}