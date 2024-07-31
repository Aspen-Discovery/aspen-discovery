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

		{*Cookie Preferences*}
		<h1>{translate text='My Cookie & Analytics Preferences' isPublicFacing=true}</h1>
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
				{if !empty($showUsernameField)}
					<div class="form-group propertyRow">
						<label for="username">{translate text="Username" isPublicFacing=true}</label>
						<input type="text" name="username" id="username" value="{$editableUsername|escape}" size="25" minlength="6" maxlength="25" class="form-control">
						<a id="usernameHelpButton" href="#" role="button" aria-controls="usernameHelp" aria-expanded="false"><i class="fa fa-question-circle" role="presentation"></i> {translate text="What is this?" isPublicFacing=true}</a>
						<div id="usernameHelp" style="display:none">
							<p>{translate text="A username is an optional feature. If you set one, your username will be your alias on hold slips and can also be used to log into your account in place of your card number.  A username can be set, reset or removed from the “My Preferences” section of your online account. Usernames must be between 6 and 25 characters (letters and number only, no special characters)." isPublicFacing=true}</p>
						</div>
					</div>
				{/if}

				{if !empty($loggedIn) && !empty($cookieConsentEnabled)}
					<div class="form-group #propertyRow">
						<strong class="control-label">{translate text="Cookies to allow" isPublicFacing=true}:</strong>&nbsp;
						<div style='padding:0.5em 1em;'>

							{*Essential Cookies*}
							<div class="form-group propertyRow row">
								<div class="col-xs-6 col-sm-4">
									<label for='userCookieEssential' class="control-label">{translate text="Essential" isPublicFacing=true}</label>&nbsp;
								</div>
								<div class="col-xs-6 col-sm-8">
									<input disabled="disabled" type="checkbox" class="form-control" name="userCookieEssential" id="userCookieEssential" {if $profile->userCookiePreferenceEssential==1}checked='checked'{/if} data-switch="">
								</div>
							</div>
					</div>

					<div class="form-group #propertyRow">
						<strong class="control-label">{translate text="Analytics Tracking to allow" isPublicFacing=true}:</strong>&nbsp;
											{*Analytics Cookies*}
						<div style='padding:0.5em 1em;'>
							<div class="form-group propertyRow row">
								<div class="col-xs-6 col-sm-4">
									<label for='userCookieAnalytics' class="control-label">{translate text="Google Analytics" isPublicFacing=true}</label>&nbsp;
								</div>
								<div class="col-xs-6 col-sm-8">
									<input type="checkbox" class="form-control" name="userCookieAnalytics" id="userCookieAnalytics" {if $profile->userCookiePreferenceAnalytics==1}checked='checked'{/if} data-switch="">
								</div>
							</div>
						</div>
					

							{*Show External Search Preferences if any relevant modules are enabled*}
							
							{if array_key_exists('Axis 360', $enabledModules) || array_key_exists('EBSCO EDS', $enabledModules) || array_key_exists('EBSCOhost', $enabledModules) || array_key_exists('Summon', $enabledModules) || array_key_exists('Hoopla', $enabledModules) || array_key_exists('OverDrive', $enabledModules)
							|| array_key_exists('Palace Project', $enabledModules) || array_key_exists('Side Loads', $enabledModules) || array_key_exists('Side Loads', $enabledModules)}
							<div style='padding:0.5em 1em;'>

								<div class="form-group propertyRow row">
									<div class="col-xs-6 col-sm-4">
										<label for='userCookieUserExternalSearchServices' class="control-label">{translate text="External Search Services" isPublicFacing=true}</label>&nbsp;<i class="fas fa-question-circle" onclick="return displayMyCookieExplanation()"></i>
									</div>
									<div class="col-xs-6 col-sm-8">
										<input type="checkbox" class="form-control" name="userCookieUserExternalSearchServices" id="userCookieUserExternalSearchServices" {if $profile->userCookiePreferenceExternalSearchServices==1}checked='checked'{/if} data-switch="">
									</div>
										<div id="myCookieExplanation" style="display:none; margin-top:10px;" class="btn-sm">
										By checking this box you are giving consent to the tracking of your usage of:
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
								</div>
							</div>
							{/if}

							{*Show Events toggle if Events module is enabled*}
							{if array_key_exists('Events', $enabledModules)}
								<div style='padding:0.5em 1em;'>
									<div class="form-group propertyRow row">
										<div class="col-xs-6 col-sm-4">
											<label for='userCookieUserEvents' class="control-label">{translate text="Events" isPublicFacing=true}</label>&nbsp;
										</div>
										<div class="col-xs-6 col-sm-8">
											<input type="checkbox" class="form-control" name="userCookieUserEvents" id="userCookieUserEvents" {if $profile->userCookiePreferenceEvents==1}checked='checked'{/if} data-switch="">
										</div>
									</div>
								</div>
							{/if}

							{if array_key_exists('Open Archives', $enabledModules)}
								<div style='padding:0.5em 1em;'>
									<div class="form-group propertyRow row">
										<div class="col-xs-6 col-sm-4">
											<label for='userCookieUserOpenArchives' class="control-label">{translate text="Open Archives" isPublicFacing=true}</label>&nbsp;
										</div>
										<div class="col-xs-6 col-sm-8">
											<input type="checkbox" class="form-control" name="userCookieUserOpenArchives" id="userCookieUserOpenArchives" {if $profile->userCookiePreferenceOpenArchives==1}checked='checked'{/if} data-switch="">
										</div>
									</div>
								</div>
							{/if}

							<div style='padding:0.5em 1em;'>
								<div class="form-group propertyRow row">
									<div class="col-xs-6 col-sm-4">
										<label for='userCookieUserWebsite' class="control-label">{translate text="Website" isPublicFacing=true}</label>&nbsp;
									</div>
									<div class="col-xs-6 col-sm-8">
										<input type="checkbox" class="form-control" name="userCookieUserWebsite" id="userCookieUserWebsite" {if $profile->userCookiePreferenceWebsite==1}checked='checked'{/if} data-switch="">
									</div>
								</div>
							</div>

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
	{else}
		<div class="page">
			{translate text="You must sign in to view this information." isPublicFacing=true}<a href='/MyAccount/Login' class="btn btn-primary">{translate text="Sign In" isPublicFacing=true}</a>
		</div>
	{/if}
	</div>
{/strip}