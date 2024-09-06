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
        <div id="cookieExplanation" style="display:none; margin-top:10px;">
                    {translate text="By checking this box you are giving consent to local analytics tracking. Aspen will collect information about your usage of the following services: "}
                    <ul>
                        {if array_key_exists('Axis 360', $enabledModules)}
                        <li>{translate text="Boundless" isPublicFacing=true}</li>
                        {/if}
                        {if array_key_exists('Cloud Library', $enabledModules)}
                            <li>{translate text="cloudLibrary" isPublicFacing=true}</li>
                        {/if}
                        {if array_key_exists('EBSCO EDS', $enabledModules)}
                            <li>{translate text="Ebsco Eds" isPublicFacing=true}</li>
                        {/if}
                        {if array_key_exists('EBSCOhost', $enabledModules)}
                            <li>{translate text="Ebsco Host" isPublicFacing=true}</li>
                        {/if}
                        {if array_key_exists('Events', $enabledModules)}
                            <li>{translate text="Events" isPublicFacing=true}</li>
                        {/if}
                        {if array_key_exists('Hoopla', $enabledModules)}
                            <li>{translate text="Hoopla" isPublicFacing=true}</li>
                        {/if}
                        {if array_key_exists('Open Archives', $enabledModules)}
                            <li>{translate text="Open Archives" isPublicFacing=true}</li>
                        {/if}
                        {if array_key_exists('OverDrive', $enabledModules)}
                            <li>{translate text="Libby" isPublicFacing=true}</li>
                        {/if}
                        {if array_key_exists('Palace Project', $enabledModules)}
                            <li>{translate text="Palace Project" isPublicFacing=true}</li>
                        {/if}
                        {if array_key_exists('Side Loads', $enabledModules)}
                            <li>{translate text="External eContent" isPublicFacing=true}</li>
                        {/if}
                        {if array_key_exists('Summon', $enabledModules)}
                            <li>{translate text="Summon" isPublicFacing=true}</li>
                        {/if}
                        {if array_key_exists('Web Indexer', $enabledModules)}
                            <li>{translate text="Library Website" isPublicFacing=true}</li>
                        {/if}
                    </ul>
                    {translate text="For more information, please see our "}<a style="cursor:pointer;" onclick="window.location = '/Help/CookieConsentPrivacyPolicy';">{translate text=" Cookie Consent Privacy Policy"}</a>
        </div>
    </form>
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
