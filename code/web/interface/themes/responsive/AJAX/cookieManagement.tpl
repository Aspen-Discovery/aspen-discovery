{if $loggedIn}
    <script>
    cookieValues = {
        Essential: {$profile->userCookiePreferenceEssential},
        Analytics: {$profile->userCookiePreferenceAnalytics},
        UserEvents: {$profile->userCookiePreferenceEvents},
        UserOpenArchives: {$profile->userCookiePreferenceOpenArchives},
        UserWebsite: {$profile->userCookiePreferenceWebsite},
        UserExternalSearchServices: {$profile->userCookiePreferenceExternalSearchServices},
    };
    </script> 
   <div>
        <form method="post" name="cookieManagementPreferencesForm" id="cookieManagementPreferencesForm" class="form">
        <div>
        <label>
            <input type="checkbox" name="cookieEssential" id="cookieEssential" checked disabled> {translate text="Essential Cookies" isPublicFacing=true}
        </label>
        </div>
        <div>
            <label>
                <input type="checkbox" name="cookieAnalytics" id="cookieAnalytics"> {translate text="Google Analytics Cookies" isPublicFacing=true}
            </label>
        </div>
         {if array_key_exists('Axis 360', $enabledModules) || array_key_exists('EBSCO EDS', $enabledModules) || array_key_exists('EBSCOhost', $enabledModules) || array_key_exists('Summon', $enabledModules) || array_key_exists('OverDrive', $enabledModules)
        || array_key_exists('Palace Project', $enabledModules) || array_key_exists('Hoopla', $enabledModules) || array_key_exists('Side Loads', $enabledModules) || array_key_exists('Cloud Library', $enabledModules) || array_key_exists('Web Indexer', $enabledModules)}
            <div>
                <label>
                    <input type="checkbox" name="cookieUserExternalSearchServices" id="cookieUserExternalSearchServices"> {translate text="External Search Services" isPublicFacing=true}&nbsp;<i class="fas fa-question-circle" onclick="return displayCookieExplanation()"></i>
                </label>
            </div>
            <div id="cookieExplanation" style="display:none; margin-top:10px;">
                {translate text="By checking this box you are giving consent to the tracking of your usage of:" isPublicFacing=true}
                <ul>
                {if array_key_exists('Axis 360', $enabledModules)}
                <li>{translate text="Axis 360" isPublicFacing=true}</li>
                {/if}
                {if array_key_exists('Cloud Library', $enabledModules)}
                    <li>{translate text="Cloud Library" isPublicFacing=true}</li>
                {/if}
                {if array_key_exists('EBSCO EDS', $enabledModules)}
                    <li>{translate text="Ebsco Eds" isPublicFacing=true}</li>
                {/if}
                {if array_key_exists('EBSCOhost', $enabledModules)}
                    <li>{translate text="Ebsco Host" isPublicFacing=true}</li>
                {/if}
                {if array_key_exists('Hoopla', $enabledModules)}
                    <li>{translate text="Hoopla" isPublicFacing=true}</li>
                {/if}
                {if array_key_exists('OverDrive', $enabledModules)}
                    <li>{translate text="Overdrive" isPublicFacing=true}</li>
                {/if}
                {if array_key_exists('Palace Project', $enabledModules)}
                    <li>{translate text="Palace Project" isPublicFacing=true}</li>
                {/if}
                {if array_key_exists('Side Loads', $enabledModules)}
                    <li>{translate text="Side Loaded eContent" isPublicFacing=true}</li>
                {/if}
                {if array_key_exists('Summon', $enabledModules)}
                    <li>{translate text="Summon" isPublicFacing=true}</li>
                {/if}
                </ul>
            </div>
         {/if}
        {if array_key_exists('Events', $enabledModules)}
            <div>
            <label>
                <input type="checkbox" name="cookieUserEvents" id="cookieUserEvents">{translate text="Events" isPublicFacing=true}
            </label>
            </div>
        {/if} 
        {if array_key_exists('Open Archives', $enabledModules)}
            <div>
                <label>
                    <input type="checkbox" name="cookieUserOpenArchives" id="cookieUserOpenArchives">{translate text="Open Archives" isPublicFacing=true}
                </label>
            </div>
        {/if}
        {if array_key_exists('Web Indexer', $enabledModules)}
            <div>
                <label>
                    <input type="checkbox" name="cookieUserWebsite" id="cookieUserWebsite"> {translate text="Website" isPublicFacing=true}
                </label>
            </div> 
        {/if}
    </div>
 {/if}

<script type="text/javascript">
{literal}
function displayCookieExplanation() {
    var explanationDiv = document.getElementById("cookieExplanation");
    if (explanationDiv.style.display === "none") {
        explanationDiv.style.display = "block";
    } else {
        explanationDiv.style.display = "none";
    }
    return false;
}
$("#cookieManagementPreferencesForm").validate({
    submitHandler: function(){
        AspenDiscovery.CookieConsent.cookieManagementPreferences();
    }
});
{/literal}
</script>