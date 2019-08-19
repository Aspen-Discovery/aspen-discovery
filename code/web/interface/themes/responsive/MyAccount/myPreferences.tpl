{strip}
	<div id="main-content">
		{if $loggedIn}
			{if !empty($profile->_web_note)}
				<div class="row">
					<div id="web_note" class="alert alert-info text-center col-xs-12">{$profile->_web_note}</div>
				</div>
			{/if}

			{* Alternate Mobile MyAccount Menu *}
			{include file="MyAccount/mobilePageHeader.tpl"}

			<span class='availableHoldsNoticePlaceHolder'></span>

			<h1>{translate text='My Preferences'}</h1>
			{if $offline}
				<div class="alert alert-warning">{translate text=offline_notice defaultText="<strong>The library system is currently offline.</strong> We are unable to retrieve information about your account at this time."}</div>
			{else}
{* MDN 7/26/2019 Do not allow access to preferences for linked users *}
{*				{include file="MyAccount/switch-linked-user-form.tpl" label="View Account Settings for" actionPath="/MyAccount/MyPreferences"}*}

				{*User Preference Options*}
				{if $showAlternateLibraryOptions || ($showRatings && $showComments)}
					{* Empty action attribute uses the page loaded. this keeps the selected user patronId in the parameters passed back to server *}
					<form action="" method="post" class="form-horizontal">
						<input type="hidden" name="updateScope" value="userPreference">

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

						{if $showAlternateLibraryOptions}
							{if count($locationList) > 2} {* First option is none *}
								<div class="form-group">
									<div class="col-xs-4"><label for="myLocation1" class="control-label">{translate text='My First Alternate Library'}</label></div>
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
									<div class="col-xs-4"><label for="myLocation2" class="control-label">{translate text='My Second Alternate Library'}</label></div>
									<div class="col-xs-8">{if $edit == true}{html_options name="myLocation2" id="myLocation2" class="form-control" options=$locationList selected=$profile->myLocation2Id}{else}{$profile->_myLocation2|escape}{/if}</div>
								</div>
							{/if}
						{/if}

						<div class="form-group">
							<div class="col-xs-4"><label for="rememberHoldPickupLocation" class="control-label" style="text-align:left">{translate text='one_click_hold_prefs' defaultText='Bypass pickup location prompt when placing holds'}</label></div>
							<div class="col-xs-8">
                                {if $edit == true}
									<input type="checkbox" class="form-control" name="rememberHoldPickupLocation" id="rememberHoldPickupLocation" {if $profile->rememberHoldPickupLocation==1}checked='checked'{/if} data-switch="">
                                {else}
                                    {if $profile->rememberHoldPickupLocation==0}No{else}Yes{/if}
                                {/if}
							</div>
						</div>

						{if !$offline && $edit == true}
							<div class="form-group">
								<div class="col-xs-8 col-xs-offset-4">
									<button type="submit" name="updateMyPreferences" class="btn btn-sm btn-primary">{translate text="Update My Preferences"}</button>
								</div>
							</div>
						{/if}
					</form>
				{/if}

				<script type="text/javascript">
					{* Initiate any checkbox with a data attribute set to data-switch=""  as a bootstrap switch *}
					{literal}
					$(function(){ $('input[type="checkbox"][data-switch]').bootstrapSwitch()});
					{/literal}
				</script>
			{/if}
		{else}
			<div class="page">
				You must login to view this information. Click <a href="{$path}/MyResearch/Login">here</a> to login.
			</div>
		{/if}
	</div>
{/strip}
