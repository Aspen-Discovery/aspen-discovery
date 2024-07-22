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

			<h1>{translate text='My Cookie Preferences' isPublicFacing=true}</h1>
			{if !empty($profileUpdateMessage)}
				{foreach from=$profileUpdateMessage item=msg}
					<div class="alert alert-success">{$msg}</div>
				{/foreach}
			{/if}
			{if !empty($offline)}
				<div class="alert alert-warning"><strong>{translate text=$offlineMessage isPublicFacing=true}</strong></div>
			{else}
				{* Empty action attribute uses the page loaded. this keeps the selected user patronId in the parameters passed back to server *}
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

					{*TODO:: At the moment, if a user ignores the cookie consent banner, the essential cookies are set to zero, so this does not show in the my accout preferences - check with MN*}
					{if !empty($loggedIn) && !empty($cookieConsentEnabled)}

						<div class="form-group #propertyRow">
						<strong class="control-label">{translate text="Cookies to allow" isPublicFacing=true}:</strong>&nbsp;
						<div style='padding:0.5em 1em;'>

						<div class="form-group propertyRow row">
							<div class="col-xs-6 col-sm-4">
								<label for='userCookieEssential' class="control-label">{translate text="Essential" isPublicFacing=true}</label>
							</div>
							<div class="col-xs-6 col-sm-8">
								<input disabled="disabled" type="checkbox" class="form-control" name="userCookieEssential" id="userCookieEssential" {if $profile->userCookiePreferenceEssential==1}checked='checked'{/if} data-switch="">
							</div>
						</div> 

						<div class="form-group propertyRow row">
							<div class="col-xs-6 col-sm-4">
								<label for='userCookieAnalytics' class="control-label">{translate text="Analytics" isPublicFacing=true}</label>
							</div>
							<div class="col-xs-6 col-sm-8">
								<input type="checkbox" class="form-control" name="userCookieAnalytics" id="userCookieAnalytics" {if $profile->userCookiePreferenceAnalytics==1}checked='checked'{/if} data-switch="">
							</div>
						</div>

						{if array_key_exists('Axis 360', $enabledModules)}
						<div class="form-group propertyRow row">
							<div class="col-xs-6 col-sm-4">
								<label for='userCookieUserAxis360' class="control-label">{translate text="Axis 360" isPublicFacing=true}</label>&nbsp;
							</div>
							<div class="col-xs-6 col-sm-8">
								<input type="checkbox" class="form-control" name="userCookieUserAxis360" id="userCookieUserAxis360" {if $profile->userCookiePreferenceAxis360==1}checked='checked'{/if} data-switch="">
							</div>
						</div>
						{/if}
						{if array_key_exists('EBSCO EDS', $enabledModules)}
						<div class="form-group propertyRow row">
							<div class="col-xs-6 col-sm-4">
								<label for='userCookieUserEbscoEds' class="control-label">{translate text="Ebsco Eds" isPublicFacing=true}</label>&nbsp;
							</div>
							<div class="col-xs-6 col-sm-8">
								<input type="checkbox" class="form-control" name="userCookieUserEbscoEds" id="userCookieUserEbscoEds" {if $profile->userCookiePreferenceEbscoEds==1}checked='checked'{/if} data-switch="">
							</div>
						</div>
						{/if}
						{if array_key_exists('EBSCOhost', $enabledModules)}
						<div class="form-group propertyRow row">
							<div class="col-xs-6 col-sm-4">
								<label for='userCookieUserEbscoHost' class="control-label">{translate text="Ebsco Host" isPublicFacing=true}</label>&nbsp;
							</div>
							<div class="col-xs-6 col-sm-8">
								<input type="checkbox" class="form-control" name="userCookieUserEbscoHost" id="userCookieUserEbscoHost" {if $profile->userCookiePreferenceEbscoHost==1}checked='checked'{/if} data-switch="">
							</div>
						</div>
						{/if}
						{if array_key_exists('Summon', $enabledModules)}
						<div class="form-group propertyRow row">
							<div class="col-xs-6 col-sm-4">
								<label for='userCookieUserSummon' class="control-label">{translate text="Summon" isPublicFacing=true}</label>&nbsp;
							</div>
							<div class="col-xs-6 col-sm-8">
								<input type="checkbox" class="form-control" name="userCookieUserSummon" id="userCookieUserSummon" {if $profile->userCookiePreferenceSummon==1}checked='checked'{/if} data-switch="">
							</div>
						</div>
						{/if}
						{if array_key_exists('Events', $enabledModules)}
						<div class="form-group propertyRow row">
							<div class="col-xs-6 col-sm-4">
								<label for='userCookieUserEvents' class="control-label">{translate text="Events" isPublicFacing=true}</label>&nbsp;
							</div>
							<div class="col-xs-6 col-sm-8">
								<input type="checkbox" class="form-control" name="userCookieUserEvents" id="userCookieUserEvents" {if $profile->userCookiePreferenceEvents==1}checked='checked'{/if} data-switch="">
							</div>
						</div>
						{/if}
						<div class="form-group propertyRow row">
							<div class="col-xs-6 col-sm-4">
								<label for='userCookieUserHoopla' class="control-label">{translate text="Hoopla" isPublicFacing=true}</label>&nbsp;
							</div>
							<div class="col-xs-6 col-sm-8">
								<input type="checkbox" class="form-control" name="userCookieUserHoopla" id="userCookieUserHoopla" {if $profile->userCookiePreferenceHoopla==1}checked='checked'{/if} data-switch="">
							</div>
						</div>
						{if array_key_exists('Open Archives', $enabledModules)}
						<div class="form-group propertyRow row">
							<div class="col-xs-6 col-sm-4">
								<label for='userCookieUserOpenArchives' class="control-label">{translate text="Open Archives" isPublicFacing=true}</label>&nbsp;
							</div>
							<div class="col-xs-6 col-sm-8">
								<input type="checkbox" class="form-control" name="userCookieUserOpenArchives" id="userCookieUserOpenArchives" {if $profile->userCookiePreferenceOpenArchives==1}checked='checked'{/if} data-switch="">
							</div>
						</div>
						{/if}

						<div class="form-group propertyRow row">
							<div class="col-xs-6 col-sm-4">
								<label for='userCookieUserOverdrive' class="control-label">{translate text="Overdrive" isPublicFacing=true}</label>&nbsp;
							</div>
							<div class="col-xs-6 col-sm-8">
								<input type="checkbox" class="form-control" name="userCookieUserOverdrive" id="userCookieUserOverdrive" {if $profile->userCookiePreferenceOverdrive==1}checked='checked'{/if} data-switch="">
							</div>
						</div>
						
						{if array_key_exists('Palace Project', $enabledModules)}
						<div class="form-group propertyRow row">
							<div class="col-xs-6 col-sm-4">
								<label for='userCookieUserPalaceproject' class="control-label">{translate text="Palace Project" isPublicFacing=true}</label>&nbsp;
							</div>
							<div class="col-xs-6 col-sm-8">
								<input type="checkbox" class="form-control" name="userCookieUserPalaceProject" id="userCookieUserPalaceProject" {if $profile->userCookiePreferencePalaceProject==1}checked='checked'{/if} data-switch="">
							</div>
						</div>
						{/if}

						<div class="form-group propertyRow row">
							<div class="col-xs-6 col-sm-4">
								<label for='userCookieUserSideLoad' class="control-label">{translate text="Side Load" isPublicFacing=true}</label>&nbsp;
							</div>
							<div class="col-xs-6 col-sm-8">
								<input type="checkbox" class="form-control" name="userCookieUserSideLoad" id="userCookieUserSideLoad" {if $profile->userCookiePreferenceSideLoad==1}checked='checked'{/if} data-switch="">
							</div>
						</div>

						<div class="form-group propertyRow row">
							<div class="col-xs-6 col-sm-4">
								<label for='userCookieUserCloudLibrary' class="control-label">{translate text="Cloud Library" isPublicFacing=true}</label>&nbsp;
							</div>
							<div class="col-xs-6 col-sm-8">
								<input type="checkbox" class="form-control" name="userCookieUserCloudLibrary" id="userCookieUserCloudLibrary" {if $profile->userCookiePreferenceCloudLibrary==1}checked='checked'{/if} data-switch="">
							</div>
						</div>

						<div class="form-group propertyRow row">
							<div class="col-xs-6 col-sm-4">
								<label for='userCookieUserWebsite' class="control-label">{translate text="Website" isPublicFacing=true}</label>&nbsp;
							</div>
							<div class="col-xs-6 col-sm-8">
								<input type="checkbox" class="form-control" name="userCookieUserWebsite" id="userCookieUserWebsite" {if $profile->userCookiePreferenceWebsite==1}checked='checked'{/if} data-switch="">
							</div>
						</div>
					{/if}

					{if empty($offline) && $edit == true}
						<div class="form-group propertyRow">
							<button type="submit" name="updateMyCookiePreferences" class="btn btn-sm btn-primary">{translate text="Update My Cookie Preferences" isPublicFacing=true}</button>
						</div>
					{/if}
				</form>

				<script type="text/javascript">
					{* Initiate any checkbox with a data attribute set to data-switch=""  as a bootstrap switch *}
					{literal}
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