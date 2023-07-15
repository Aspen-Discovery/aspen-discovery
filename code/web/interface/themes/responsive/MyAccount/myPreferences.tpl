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

			<h1>{translate text='My Preferences' isPublicFacing=true}</h1>
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
			{if !empty($offline)}
				<div class="alert alert-warning"><strong>{translate text=$offlineMessage isPublicFacing=true}</strong></div>
			{else}
				{* Empty action attribute uses the page loaded. this keeps the selected user patronId in the parameters passed back to server *}
				<form action="" method="post" role="form">
					<input type="hidden" name="updateScope" value="userPreference">
					{if !empty($showUsernameField)}
						<div class="form-group propertyRow">
							<label for="username">{translate text="Username" isPublicFacing=true}</label>
							<input type="text" name="username" id="username" value="{$editableUsername|escape}" size="25" minlength="6" maxlength="25" class="form-control">
							<a href="#" onclick="$('#usernameHelp').toggle()">{translate text="What is this?" isPublicFacing=true}</a>
							<div id="usernameHelp" style="display:none">
								{translate text="A username is an optional feature. If you set one, your username will be your alias on hold slips and can also be used to log into your account in place of your card number.  A username can be set, reset or removed from the “My Preferences” section of your online account. Usernames must be between 6 and 25 characters (letters and number only, no special characters)." isPublicFacing=true}
							</div>
						</div>
					{/if}

					{if count($validLanguages) > 1}
						<div class="form-group propertyRow">
							<label for="profileLanguage" class="control-label">{translate text='Language to display catalog in' isPublicFacing=true}</label>
							<select id="profileLanguage" name="profileLanguage" class="form-control">
								{foreach from=$validLanguages key=languageCode item=language}
									<option value="{$languageCode}"{if $profile->interfaceLanguage==$languageCode} selected="selected"{/if}>
										{$language->displayName}
									</option>
								{/foreach}
							</select>
						</div>
					{/if}

					{if count($allActiveThemes) > 1}
						<div class="form-group propertyRow">
							<label for="preferredTheme" class="control-label">{translate text='Display Mode' isPublicFacing=true}</label>
							<select id="preferredTheme" name="preferredTheme" class="form-control">
								{foreach from=$allActiveThemes key=themeId item=themeName}
									<option value="{$themeId}"{if $profile->preferredTheme==$themeId} selected="selected"{/if}>
										{$themeName}
									</option>
								{/foreach}
							</select>
						</div>
					{/if}

					<div class="form-group propertyRow" id="searchPreferenceLanguageGroup" {if $profile->interfaceLanguage=='en'}style="display:none"{/if}>
						<label for="searchPreferenceLanguage" class="control-label">{translate text="Do you want prefer materials in %1%?" 1=$userLang->displayName isPublicFacing=true}</label>
						<select name="searchPreferenceLanguage" id="searchPreferenceLanguage" class="form-control">
							<option value="0" {if $profile->searchPreferenceLanguage == 0}selected{/if}>{translate text="No, show interfiled with other languages" isPublicFacing=true}</option>
							<option value="1" {if $profile->searchPreferenceLanguage == 1}selected{/if}>{translate text="Yes, show above other languages" isPublicFacing=true}</option>
							<option value="2" {if $profile->searchPreferenceLanguage == 2}selected{/if}>{translate text="Yes, only show my preferred language" isPublicFacing=true}</option>
						</select>
					</div>

					{if !empty($showRatings) && $showComments}
						<div class="form-group propertyRow">
							<label for="noPromptForUserReviews" class="control-label">{translate text='Do not prompt me for reviews after rating titles' isPublicFacing=true}</label>&nbsp;
							{if $edit == true}
								<input type="checkbox" class="form-control" name="noPromptForUserReviews" id="noPromptForUserReviews" {if $profile->noPromptForUserReviews==1}checked='checked'{/if} data-switch="">
							{else}
								{if $profile->noPromptForUserReviews==0} {translate text='No' isPublicFacing=true}{else} {translate text='Yes' isPublicFacing=true}{/if}
							{/if}
							<p class="help-block alert alert-info">
								<span class="glyphicon glyphicon-info-sign" aria-hidden="true"></span> {translate text="When you rate an item by clicking on the stars, you will be asked to review that item also. Selecting this option lets us know you don't want to give reviews after you have rated an item by clicking its stars." isPublicFacing=true}
							</p>
						</div>
					{/if}

					{if !empty($showEdsPreferences)}
						<div class="form-group propertyRow">
							<label for="hideResearchStarters" class="control-label">{translate text='Hide Research Starters' isPublicFacing=true}</label>&nbsp;
							{if $edit == true}
								<input type="checkbox" class="form-control" name="hideResearchStarters" id="hideResearchStarters" {if $profile->hideResearchStarters==1}checked='checked'{/if} data-switch="">
							{else}
								&nbsp;{if $profile->hideResearchStarters==0} {translate text='No' isPublicFacing=true}{else} {translate text='Yes' isPublicFacing=true}{/if}
							{/if}
						</div>
					{/if}

					{if !empty($allowHomeLibraryUpdates)}
						{* Allow editing home library *}
						<div class="form-group  propertyRow">
							<label for="homeLocation" class="control-label">{translate text='Home Library' isPublicFacing=true}</label>
							{if $edit == true && $canUpdateContactInfo == true}
								<select name="homeLocation" id="homeLocation" class="form-control">
									{if count($homeLibraryLocations) > 0}
										{foreach from=$homeLibraryLocations item=location}
											{if is_object($location)}
												<option value="{$location->code}" {if $location->locationId == $profile->homeLocationId}selected="selected"{/if}>{$location->displayName}</option>
											{else}
												<option value="">{$location}</option>
											{/if}
										{/foreach}
									{else}
										<option>placeholder</option>
									{/if}
								</select>
							{else}
								&nbsp;{$profile->getHomeLocationName()}
							{/if}
						</div>
					{else}
						<div class="form-group propertyRow">
							<strong>{translate text='Home Library' isPublicFacing=true}</strong> {$profile->getHomeLocationName()}
						</div>
					{/if}

					{if !empty($allowRememberPickupLocation) && count($pickupLocations) > 1}
						{* Allow editing the pickup location *}
						<div class="form-group propertyRow">
							<label for="pickupLocation" class="control-label">{translate text='Preferred Pickup Location' isPublicFacing=true}</label>
							{if $edit == true && !empty($allowPickupLocationUpdates)}
								<select name="pickupLocation" id="pickupLocation" class="form-control">
									{if count($pickupLocations) > 0}
										{foreach from=$pickupLocations item=location}
											{if is_object($location)}
												<option value="{$location->locationId}" {if $location->locationId == $profile->pickupLocationId}selected="selected"{/if}>{$location->displayName}</option>
											{else}
												<option value="0">{$location}</option>
											{/if}
										{/foreach}
									{else}
										<option>placeholder</option>
									{/if}
								</select>
							{else}
								&nbsp;{$profile->getPickupLocationName()|escape}
							{/if}
						</div>
					{/if}

					{if !empty($showAlternateLibraryOptions)}
						{if count($locationList) > 2} {* First option is none *}
							<div class="form-group propertyRow">
								<label for="myLocation1" class="control-label">{translate text='Alternate Pickup Location 1' isPublicFacing=true}</label>
								{if $edit == true}
									{html_options name="myLocation1" id="myLocation1" class="form-control" options=$locationList selected=$profile->myLocation1Id}
								{else}
									&nbsp;{$profile->_myLocation1|escape}
								{/if}
							</div>
						{/if}
						{if count($locationList) > 3} {* First option is none *}
							<div class="form-group propertyRow">
								<label for="myLocation2" class="control-label">{translate text='Alternate Pickup Location 2' isPublicFacing=true}</label>
								&nbsp;{if $edit == true}{html_options name="myLocation2" id="myLocation2" class="form-control" options=$locationList selected=$profile->myLocation2Id}{else}{$profile->_myLocation2|escape}{/if}
							</div>
						{/if}
					{/if}

					{if !empty($allowRememberPickupLocation)}
						<div class="form-group propertyRow">
							<label for="rememberHoldPickupLocation" class="control-label">{translate text='Bypass pickup location prompt when placing holds' isPublicFacing=true}</label>&nbsp;
							{if $edit == true}
								<input type="checkbox" class="form-control" name="rememberHoldPickupLocation" id="rememberHoldPickupLocation" {if $profile->rememberHoldPickupLocation==1}checked='checked'{/if} data-switch="">
							{else}
								{if $profile->rememberHoldPickupLocation==0}{translate text="No" isPublicFacing=true}{else}{translate text="Yes" isPublicFacing=true}{/if}
							{/if}
						</div>
					{/if}

					{if !empty($showAutoRenewSwitch)}
						<div class="form-group propertyRow">
							<label for="allowAutoRenewal" class="control-label">{translate text='Allow Auto Renewal' isPublicFacing=true}</label>&nbsp;
							{if $edit == true}
								<input type="checkbox" class="form-control" name="allowAutoRenewal" id="allowAutoRenewal" {if $autoRenewalEnabled==1}checked='checked'{/if} data-switch="">
							{else}
								{if $profile->autoRenewalEnabled==0}{translate text="No" isPublicFacing=true}{else}{translate text="Yes" isPublicFacing=true}{/if}
							{/if}
						</div>
					{/if}

					{if empty($offline) && $edit == true}
						<div class="form-group propertyRow">
							<button type="submit" name="updateMyPreferences" class="btn btn-sm btn-primary">{translate text="Update My Preferences" isPublicFacing=true}</button>
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
				{translate text="You must sign in to view this information." isPublicFacing=true}<a href='/MyAccount/Login' class="btn btn-primary">{translate text="Sign In" isPublicFacing=true}</a>
			</div>
		{/if}
	</div>
{/strip}
