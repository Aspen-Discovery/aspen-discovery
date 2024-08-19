{if $loggedIn}
<script>
    cookieValues = {
        Essential: {$profile->userCookiePreferenceEssential},
        Analytics: {$profile->userCookiePreferenceAnalytics},
        UserLocalAnalytics: {$profile->userCookiePreferenceLocalAnalytics},
    }
</script>
<div>
    <form method="post" name="cookieManagementPreferencesForm" id="cookieManagementPreferencesForm" class="form">
        <div>
            <label>
                <input type="checkbox" name="cookieEssential" id="cookieEssential" checked disabled>&nbsp;{translate text="Essential Cookies" isPublicFacing=true}
            </label>
        </div>
        <div>
            <label>
                <input type="checkbox" name="cookieAnalytics" id="cookieAnalytics" {if $profile->userCookiePreferenceAnalytics==1}checked="checked"{/if} data-switch="">&nbsp;{translate text="Google Analytics Cookies" isPublicFacing=true}
            </label>
        </div>
        {if array_key_exists('Axis 360', $enabledModules) || array_key_exists('EBSCO EDS', $enabledModules) || array_key_exists('EBSCOhost', $enabledModules) || array_key_exists('Summon', $enabledModules) || array_key_exists('OverDrive', $enabledModules)
        || array_key_exists('Palace Project', $enabledModules) || array_key_exists('Hoopla', $enabledModules) || array_key_exists('Side Loads', $enabledModules) || array_key_exists('Cloud Library', $enabledModules) || array_key_exists('Web Indexer', $enabledModules) || array_key_exists('Events', $enabledModules) || array_key_exists('Open Archives', $enabledModules)
         || array_key_exists('Web Indexer', $enabledModules)}
        <div>
            <label>
                <input type="checkbox" name="cookieUserLocalAnalytics" id="cookieUserLocalAnalytics" {if $profile->userCookiePreferenceLocalAnalytics==1}checked="checked"{/if} data-switch="">&nbsp;{translate text="Local Analytics" isPublicFacing=true}&nbsp;<i class="fas fa-question-circle" onclick="return displayCookieExplanation()"></i>
            </label>
        </div>
        {/if}
    </form>
</div>
{/if}

<script type="text/javascript">
{literal}
    $("#cookieManagementPreferencesForm").validate({
        submitHandler: function(){
            AspenDiscovery.CookieConsent.cookieManagementPreferences();
        }
    });
{/literal}
</script>
