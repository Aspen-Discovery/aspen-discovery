{strip}
	<div id="main-content">
	{if !empty($loggedIn)}
		{if !empty($profile->_web_note)}
			<div class="row">
				<div id="web_note" class="alert alert-info text-center col-xs-12">{$profile->_web_note}</div>
			</div>
		{/if}
		{if !empty($accountMessages)}
			{include file='systemMessages.tpl' messages=$accountMessages}
		{/if}
		{if !empty($ilsMessages)}
			{include file='ilsMessages.tpl' messages=$ilsMessages}
		{/if}

		<h1>{translate text="My Privacy Settings" isPublicFacing=true}</h1>
		{if !empty($profileUpdateMessage)}
			{foreach from=$profileUpdateMessage item=msg}
				<div class="alert alert-success">{$msg}</div>
			{/foreach}
		{/if}

		{if !empty($offline)}
			<div class="alert alert-warning"><strong>{translate text=$offlineMessage isPublicFacing=true}</strong></div>
		{else}
			<form action="" method="post" role="form">
				<input type="hidden" name="updateScope" value="userCookiePreference">
				<input type="hidden" name="patronId" value={$profile->id|escape}>
				{if !empty($loggedIn) && !empty($cookieConsentEnabled)}
					{*Essential Cookies*}
					<div class="form-group #propertyRow" style="margin-bottom:10px;">
						<strong class="control-label" style="margin-bottom:10px;">{translate text="Essential Cookies" isPublicFacing=true}:</strong>&nbsp;
						<div class="padding:0.5em 1em;">
							<div class="col-xs-6 col-sm-4">
								<label for="userCookieEssential" class="control-label">{translate text="Essential" isPublicFacing=true}</label>&nbsp;
							</div>
							<div class="col-xs-6 col-sm-8">
								<input disabled="disabled" type="checkbox" class="form-control" name="userCookieEssential" id="userCookieEssential" {if $profile->userCookiePreferenceEssential==1}checked="checked"{/if} data-switch="">
							</div>
						</div>
					</div>
					{*Third Party Analytics*}
					<div class="form-group #propertyRow" style="margin-bottom:10px;">
						<strong class="control-label" style="margin-bottom:10px;">{translate text="Thrid Party Analytics" isPublicFacing=true}:</strong>&nbsp;
						<div class="padding:0.5em 1em;">
							<div class="col-xs-6 col-sm-4">
								<label for="userCookieAnalytics" class="control-label">{translate text="Google Analytics" isPublicFacing=true}</label>&nbsp;
							</div>
							<div class="col-xs-6 col-sm-8">
								<input type="checkbox" class="form-control" name="userCookieAnalytics" id="userCookieAnalytics" {if $profile->userCookiePreferenceAnalytics==1}checked="checked"{/if} data-switch="">
							</div>
						</div>
					</div>
					{*Local Analytics*}
					<div class="form-group #propertyRow" style="margin-bottom:10px;">
					<strong class="control-label" style="margin-bottom:10px;">{translate text="Local Analytics" isPublicFacing=true}:</strong>&nbsp;
					<div class="padding:0.5em 1em;">
						<div class="col-xs-6 col-sm-4">
							<label for="userCookieUserLocalAnalytics" class="control-label">{translate text="Local Analytics" isPublicFacing=true}</label>&nbsp;<i class="fas fa-question-circle" onclick="return displayMyCookieExplanation()"></i>
						</div>
						<div class="col-xs-6 col-sm-8">
							<input type="checkbox" class="form-control" name="userCookieUserLocalAnalytics" id="userCookieLocalAnalytics" {if $profile->userCookiePreferenceLocalAnalytics==1}checked="checked"{/if} data-switch="">
						</div>
					</div>
					<div id="myCookieExplanation" style="display:none; margin-top: 10px;">
							{translate text="By checking this box you are giving consent to local analytics tracking. Aspen will collect information about your usage of the following services: "}
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
								{if array_key_exists('Web Indexer', $enabledModules)}
									<li>{translate text="Web Indexer" isPublicFacing=true}</li>
								{/if}
                    		</ul>
                    		{translate text="For more information, please see our "}<a onclick="AspenDiscovery.CookieConsent.ViewCookieConsentPolicy()">{translate text=" Cookie Consent Privacy Policy"}</a>
					</div>
				</div>
				{/if}
				{if empty($offline) && $edit == true}
					<div class="form-group propertyRow">
						<button type="submit" name="updateMyCookiePreferences" class="btn btn-sm btn-primary">{translate text="Update My Preferences" isPublicFacing=true}</button>
					</div>
				{/if}
			</form>
			<script type="text/javascript">
			{* Initiate any checkbox with a data attribute set to data-switch=""  as a bootstrap switch *}
			{literal}
			function displayMyCookieExplanation () {
				var explanationDiv = document.getElementById("myCookieExplanation");
				if (explanationDiv.style.display === "none") {
					explanationDiv.style.display = "block";
				} else {
					explanationDiv.style.display = "none";
				}
			}
			$(function(){ $('input[type="checkbox"][data-switch]').bootstrapSwitch()});
			$("#usernameHelpButton").click(function() {
				var helpButton = $(this);
				if (helpButton.attr("aria-expanded") === "true") {
					$("#usernameHelp").css('display', 'none');
					$("#usernameHelpButton").attr("aria-expanded","false");
				}
				else if (helpButton.attr("aria-expanded") === "false") {
					$("#usernameHelp").css('display', 'block');
					$("#usernameHelpButton").attr("aria-expanded","true");
				}
				return false;
			})
			{/literal}
		</script>
		{/if}

	{/if}
	</div>
{/strip}