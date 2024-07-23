    {if $loggedIn}
        <script>
        cookieValues = {
            Essential: {$profile->userCookiePreferenceEssential},
            Analytics: {$profile->userCookiePreferenceAnalytics},
            // UserAxis360: {$profile->userCookiePreferenceAxis360},
            // UserEbscoEds: {$profile->userCookiePreferenceEbscoEds},
            // UserEbscoHost: {$profile->userCookiePreferenceEbscoHost},
            // UserSummon: {$profile->userCookiePreferenceSummon},
            UserEvents: {$profile->userCookiePreferenceEvents},
            //UserHoopla: {$profile->userCookiePreferenceHoopla},
            UserOpenArchives: {$profile->userCookiePreferenceOpenArchives},
            //UserOverdrive: {$profile->userCookiePreferenceOverdrive},
            //UserPalaceProject: {$profile->userCookiePreferencePalaceProject},    
            //UserSideLoad: {$profile->userCookiePreferenceSideLoad},
            //UserCloudLibrary: {$profile->userCookiePreferenceCloudLibrary},
            UserWebsite: {$profile->userCookiePreferenceWebsite},
            UserExternalSearchServices: {$profile->userCookiePreferenceExternalSearchServices},
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
             {if array_key_exists('Axis 360', $enabledModules) || array_key_exists('EBSCO EDS', $enabledModules) || array_key_exists('EBSCOhost', $enabledModules) || array_key_exists('Summon', $enabledModules) || array_key_exists('OverDrive', $enabledModules)
            || array_key_exists('Palace Project', $enabledModules) || array_key_exists('Hoopla', $enabledModules) || array_key_exists('Side Loads', $enabledModules) || array_key_exists('Cloud Library', $enabledModules) || array_key_exists('Web Indexer', $enabledModules)}
                <div>
                    <label>
                        <input type="checkbox" name="cookieUserExternalSearchServices" id="cookieUserExternalSearchServices"> External Search Services&nbsp;<i class="fas fa-question-circle" onclick="return displayCookieExplanation()"></i>
                    </label>
                </div>
                <div id="cookieExplanation" style="display:none; margin-top:10px;">
                    By checking this box you are giving consent to the tracking of your usage of:
                    <ul>
                    {if array_key_exists('Axis 360', $enabledModules)}
                    <li>Axis 360</li>
                    {/if}
                    {if array_key_exists('Cloud Library', $enabledModules)}
                        <li>Cloud Library</li>
                    {/if}
                    {if array_key_exists('EBSCO EDS', $enabledModules)}
                        <li>Ebsco Eds</li>
                    {/if}
                    {if array_key_exists('EBSCOhost', $enabledModules)}
                        <li>Ebsco Host</li>
                    {/if}
                    {if array_key_exists('Hoopla', $enabledModules)}
                        <li>Hoopla</li>
                    {/if}
                    {if array_key_exists('OverDrive', $enabledModules)}
                        <li>Overdrive</li>
                    {/if}
                    {if array_key_exists('Palace Project', $enabledModules)}
                        <li>Palace Project</li>
                    {/if}
                    {if array_key_exists('Side Loads', $enabledModules)}
                        <li>Side Loaded eContent</li>
                    {/if}
                    {if array_key_exists('Summon', $enabledModules)}
                        <li>Summon</li>
                    {/if}
                    </ul>
                </div>
             {/if}
            {if array_key_exists('Events', $enabledModules)}
                <div>
                <label>
                    <input type="checkbox" name="cookieUserEvents" id="cookieUserEvents"> Events
                </label>
                </div>
            {/if} 
            {if array_key_exists('Open Archives', $enabledModules)}
                <div>
                    <label>
                        <input type="checkbox" name="cookieUserOpenArchives" id="cookieUserOpenArchives"> Open Archives
                    </label>
                </div>
            {/if}
            {if array_key_exists('Web Indexer', $enabledModules)}
                <div>
                    <label>
                        <input type="checkbox" name="cookieUserWebsite" id="cookieUserWebsite"> Website
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
