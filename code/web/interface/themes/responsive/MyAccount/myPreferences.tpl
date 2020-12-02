{strip}
	<div id="main-content">
		{if $loggedIn}
			{if !empty($profile->_web_note)}
				<div class="row">
					<div id="web_note" class="alert alert-info text-center col-xs-12">{$profile->_web_note}</div>
				</div>
			{/if}
			{if !empty($accountMessages)}
				{include file='systemMessages.tpl' messages=$accountMessages}
			{/if}

			<span class='availableHoldsNoticePlaceHolder'></span>

			<h1>{translate text='My Preferences'}</h1>
			{if !empty($profileUpdateErrors)}
				{foreach from=$profileUpdateErrors item=errorMsg}
					<div class="alert alert-danger">{$errorMsg}</div>
				{/foreach}
			{/if}
			{if !empty($profileUpdateMessage)}
				{foreach from=$profileUpdateMessage item=msg}
					<div class="alert alert-success">{$msg}</div>
				{/foreach}
			{/if}
			{if $offline}
				<div class="alert alert-warning">{translate text=offline_notice defaultText="<strong>The library system is currently offline.</strong> We are unable to retrieve information about your account at this time."}</div>
			{else}
				{* Empty action attribute uses the page loaded. this keeps the selected user patronId in the parameters passed back to server *}
				<form action="" method="post" class="form-horizontal">
					<input type="hidden" name="updateScope" value="userPreference">
					{if $showUsernameField}
						<div class="form-group">
							<div class="col-xs-4"><label for="username">{translate text="editable_username_label" defaultText="Username"}</label></div>
							<div class="col-xs-8">
								<input type="text" name="username" id="username" value="{$editableUsername|escape}" size="25" minlength="6" maxlength="25" class="form-control">
								<a href="#" onclick="$('#usernameHelp').toggle()">What is this?</a>
								<div id="usernameHelp" style="display:none">
									{translate text="editable_username_help" defaultText="A username is an optional feature. If you set one, your username will be your alias on hold slips and can also be used to log into your account in place of your card number.  A username can be set, reset or removed from the “My Preferences” section of your online account. Usernames must be between 6 and 25 characters (letters and number only, no special characters)."}
								</div>
							</div>
						</div>
					{/if}

					{if count($validLanguages) > 1}
						<div class="form-group">
							<div class="col-xs-4"><label for="profileLanguage" class="control-label">{translate text='Language to display catalog in'}</label></div>
							<div class="col-xs-8">
								<select id="profileLanguage" name="profileLanguage" class="form-control" onchange="{literal}if ($('#profileLanguage option:selected').val() === 'en') { $('#searchPreferenceLanguageGroup').hide() }else{ $('#searchPreferenceLanguageGroup').show()} {/literal}">
									{foreach from=$validLanguages key=languageCode item=language}
										<option value="{$languageCode}"{if $profile->interfaceLanguage==$languageCode} selected="selected"{/if}>
											{$language->displayName}
										</option>
									{/foreach}
								</select>
							</div>
						</div>
					{/if}

					<div class="form-group" id="searchPreferenceLanguageGroup" {if $profile->interfaceLanguage=='en'}style="display:none"{/if}>
						<div class="col-xs-4">
							<label for="searchPreferenceLanguage" class="control-label" style="text-align:left">{translate text="Do you want prefer materials in %1%?" 1=$userLang->displayName}</label>
						</div>
						<div class="col-xs-8">
							<select name="searchPreferenceLanguage" id="searchPreferenceLanguage" class="form-control">
								<option value="0" {if $profile->searchPreferenceLanguage == 0}selected{/if}>{translate text='language_preference_interfiled' defaultText="No, show interfiled with other languages"}</option>
								<option value="1" {if $profile->searchPreferenceLanguage == 1}selected{/if}>{translate text='language_preference_above' defaultText="Yes, show above other languages"}</option>
								<option value="2" {if $profile->searchPreferenceLanguage == 2}selected{/if}>{translate text='language_preference_only_preferred' defaultText="Yes, only show my preferred language"}</option>
							</select>
						</div>
					</div>

					{if $showRatings && $showComments}
						<div class="form-group">
							<div class="col-xs-4"><label for="noPromptForUserReviews" class="control-label" style="text-align:left">{translate text='Do not prompt me for reviews after rating titles'}</label></div>
							<div class="col-xs-8">
								{if $edit == true}
									<input type="checkbox" class="form-control" name="noPromptForUserReviews" id="noPromptForUserReviews" {if $profile->noPromptForUserReviews==1}checked='checked'{/if} data-switch="">
								{else}
									{if $profile->noPromptForUserReviews==0}No{else}Yes{/if}
								{/if}
								<p class="help-block alert alert-warning">
									{translate text="rating_setting_explanation" defaultText="When you rate an item by clicking on the stars, you will be asked to review that item also. Setting this option to <strong>&quot;on&QUOT;</strong> lets us know you don't want to give reviews after you have rated an item by clicking its stars."}
								</p>
							</div>
						</div>
					{/if}

					{if $showEdsPreferences}
						<div class="form-group">
							<div class="col-xs-4"><label for="hideResearchStarters" class="control-label" style="text-align:left">{translate text='hide_research_starters' defaultText='Hide Research Starters'}</label></div>
							<div class="col-xs-8">
								{if $edit == true}
									<input type="checkbox" class="form-control" name="hideResearchStarters" id="hideResearchStarters" {if $profile->hideResearchStarters==1}checked='checked'{/if} data-switch="">
								{else}
									{if $profile->hideResearchStarters==0}No{else}Yes{/if}
								{/if}
							</div>
						</div>
					{/if}

					{if $showPickupLocationInProfile}
						{* Allow editing pickup location *}
						<div class="form-group">
							<div class="col-xs-4"><label for="pickupLocation" class="">{translate text='Pickup Location'}</label></div>
							<div class="col-xs-8">
								{if $edit == true && $canUpdateContactInfo == true}
									<select name="pickupLocation" id="pickupLocation" class="form-control">
										{if count($pickupLocations) > 0}
											{foreach from=$pickupLocations item=location}
												<option value="{$location->code}" {if $location->displayName|escape == $profile->_homeLocation|escape}selected="selected"{/if}>{$location->displayName}</option>
											{/foreach}
										{else}
											<option>placeholder</option>
										{/if}
									</select>
								{else}
									{$profile->_homeLocation|escape}
								{/if}
							</div>
						</div>
					{else}
						<div class="form-group">
							<div class="col-xs-4"><strong>{translate text='Main Pickup Location'}</strong></div>
							<div class="col-xs-8">{$profile->getHomeLocationName()|escape}</div>
						</div>
					{/if}

					{if $showAlternateLibraryOptions}
						{if count($locationList) > 2} {* First option is none *}
							<div class="form-group">
								<div class="col-xs-4"><label for="myLocation1" class="control-label">{translate text='Alternate Pickup Location 1'}</label></div>
								<div class="col-xs-8">
									{if $edit == true}
										{html_options name="myLocation1" id="myLocation1" class="form-control" options=$locationList selected=$profile->myLocation1Id}
									{else}
										{$profile->_myLocation1|escape}
									{/if}
								</div>
							</div>
						{/if}
						{if count($locationList) > 3} {* First option is none *}
							<div class="form-group">
								<div class="col-xs-4"><label for="myLocation2" class="control-label">{translate text='Alternate Pickup Location 2'}</label></div>
								<div class="col-xs-8">{if $edit == true}{html_options name="myLocation2" id="myLocation2" class="form-control" options=$locationList selected=$profile->myLocation2Id}{else}{$profile->_myLocation2|escape}{/if}</div>
							</div>
						{/if}
					{/if}

					<div class="form-group">
						<div class="col-xs-4"><label for="rememberHoldPickupLocation" class="control-label" style="text-align: left">{translate text='one_click_hold_prefs' defaultText='Bypass pickup location prompt when placing holds'}</label></div>
						<div class="col-xs-8">
							{if $edit == true}
								<input type="checkbox" class="form-control" name="rememberHoldPickupLocation" id="rememberHoldPickupLocation" {if $profile->rememberHoldPickupLocation==1}checked='checked'{/if} data-switch="">
							{else}
								{if $profile->rememberHoldPickupLocation==0}No{else}Yes{/if}
							{/if}
						</div>
					</div>

					{if $showAutoRenewSwitch}
						<div class="form-group">
							<div class="col-xs-4"><label for="allowAutoRenewal" class="control-label">{translate text='allow_autorenew' defaultText='Allow Auto Renewal'}</label></div>
							<div class="col-xs-8">
								{if $edit == true}
									<input type="checkbox" class="form-control" name="allowAutoRenewal" id="allowAutoRenewal" {if $autoRenewalEnabled==1}checked='checked'{/if} data-switch="">
								{else}
									{if $profile->autoRenewalEnabled==0}No{else}Yes{/if}
								{/if}
							</div>
						</div>
					{/if}

					{if !$offline && $edit == true}
						<div class="form-group">
							<div class="col-xs-8 col-xs-offset-4">
								<button type="submit" name="updateMyPreferences" class="btn btn-sm btn-primary">{translate text="Update My Preferences"}</button>
							</div>
						</div>
					{/if}
				</form>

				<script type="text/javascript">
					{* Initiate any checkbox with a data attribute set to data-switch=""  as a bootstrap switch *}
					{literal}
					$(function(){ $('input[type="checkbox"][data-switch]').bootstrapSwitch()});
					{/literal}
				</script>
			{/if}
		{else}
			<div class="page">
				You must sign in to view this information. Click <a href="/MyAccount/Login">here</a> to sign in.
			</div>
		{/if}
	</div>
{/strip}
