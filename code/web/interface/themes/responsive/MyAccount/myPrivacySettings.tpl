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
			{if !empty($cookieConsentEnabled)}
			<h2>{translate text="Cookies and analytics" isPublicFacing=true}</h2>
			<form action="" method="post" role="form">
				<input type="hidden" name="updateScope" value="userCookiePreference">
				<input type="hidden" name="patronId" value={$profile->id|escape}>
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
						<strong class="control-label" style="margin-bottom:10px;">{translate text="Third Party Analytics" isPublicFacing=true}:</strong>&nbsp;
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
							<label for="userCookieUserLocalAnalytics" class="control-label">{translate text="Local Analytics" isPublicFacing=true}</label>&nbsp;<i class="fas fa-question-circle" onclick="return displayMyConsentExplanation('localAnalytics')"></i>
						</div>
						<div class="col-xs-6 col-sm-8">
							<input type="checkbox" class="form-control" name="userCookieUserLocalAnalytics" id="userCookieLocalAnalytics" {if $profile->userCookiePreferenceLocalAnalytics==1}checked="checked"{/if} data-switch="">
						</div>
					</div>
					<div id="myCookieConsentExplanation" style="display:none; margin-top: 10px;">
							{translate text="By checking this box you are giving consent to local analytics tracking. Aspen will collect information about your usage of the following services: "}
							<ul>
								{if array_key_exists('Axis 360', $enabledModules)}
								<li>{translate text="Boundless" isPublicFacing=true}</li>
								{/if}
								{if array_key_exists('BorrowBox', $enabledModules)}
									<li>{translate text="BorrowBox" isPublicFacing=true}</li>
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
								{if array_key_exists('Gale', $enabledModules)}
									<li>{translate text="Gale" isPublicFacing=true}</li>
								{/if}
								{if array_key_exists('Web Indexer', $enabledModules)}
									<li>{translate text="Library Website" isPublicFacing=true}</li>
								{/if}
							</ul>
							{translate text="For more information, please see our "}<a  style="cursor:pointer;" onclick="window.location = '/Help/CookieConsentPrivacyPolicy';">{translate text=" Cookie Consent Privacy Policy"}</a>
					</div>
				</div>
				{if empty($offline) && $edit == true}
					<div class="form-group propertyRow">
						<button type="submit" name="updateMyConsentPreferences" class="btn btn-sm btn-primary">{translate text="Update My Preferences" isPublicFacing=true}</button>
					</div>
				{/if}
				</form>
			{/if}
			{if !empty($ilsConsentEnabled)}
				<form action="" method="post" role="form">
					<input type="hidden" name="updateScope" value="userILSIssuedConsent">
					<input type="hidden" name="patronId" value={$profile->id|escape}>
					{foreach $consentTypes as $consentType}
						<section id="{$consentType['lowercaseCode']}ConsentSection">
						<input type="hidden" name="updateScopeSection" value="{$consentType['lowercaseCode']}">
							{$consentCode = $consentType['capitalisedCode']}
							<h2>{translate text={$consentType['label']} isPublicFacing=true}</h2>
							<div class="form-group #propertyRow" style="margin-bottom:10px;">
								<strong class="control-label" style="margin-bottom:10px;">{translate text={$consentType['description']} isPublicFacing=true}</strong>&nbsp;
								<div class="padding:0.5em 1em;">
									<div class="col-xs-6 col-sm-4">
										<label for="user{$consentCode}" class="control-label">{translate text={$consentType['label']} isPublicFacing=true}</label>&nbsp;<i class="fas fa-question-circle" onclick="return displayMyConsentExplanation('{$consentCode}')"></i>
									</div>
									<div class="col-xs-6 col-sm-8">
										<input type="checkbox" class="form-control" name="user{$consentCode}" id="user{$consentCode}" {if isset($consentType['enabledForUser']) && $consentType['enabledForUser'] == true}checked="checked"{/if} data-switch="">
									</div>
								</div>
							</div>
							<div id="my{$consentCode}ConsentExplanation" style="display:none; margin-top: 10px;">
									{translate text="By checking this box you are giving your consent to {$consentType['actionConsentedTo']}. You can return to this page to update your consent at any point."}
							</div>
						</section>
					{/foreach}
					{if empty($offline) && $edit == true}
						<div class="form-group propertyRow">
							<button type="submit" name="updateMyILSIssuedConsents" class="btn btn-sm btn-primary">{translate text="Update Consent" isPublicFacing=true}</button>
						</div>
					{/if}
				</form>
			{/if}
		<script type="text/javascript">
			{* Initiate any checkbox with a data attribute set to data-switch=""  as a bootstrap switch *}
			{literal}
			function displayMyConsentExplanation (type) {
				var explanationDiv = type === 'localAnalytics'? document.getElementById("myCookieConsentExplanation") : document.getElementById(`my${type}ConsentExplanation`)
				if (explanationDiv.style.display === "none") {
					explanationDiv.style.display = "block";
				} else {
					explanationDiv.style.display = "none";
				}
			}
			$(function(){ $('input[type="checkbox"][data-switch]').bootstrapSwitch()});
			$("#usernameHelpButton").on('click', function() {
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
