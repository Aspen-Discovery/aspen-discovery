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
							<label for="userCookieUserLocalAnalytics" class="control-label">{translate text="Local Analytics" isPublicFacing=true}</label>&nbsp;
						</div>
						<div class="col-xs-6 col-sm-8">
							<input type="checkbox" class="form-control" name="userCookieUserLocalAnalytics" id="userCookieLocalAnalytics" {if $profile->userCookiePreferenceLocalAnalytics==1}checked="checked"{/if} data-switch="">
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

	{/if}
	</div>
{/strip}