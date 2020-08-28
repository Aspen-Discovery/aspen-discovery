{strip}
	<div id="main-content">
		{if $loggedIn}
			{if !empty($profile->_web_note)}
				<div class="row">
					<div id="web_note" class="alert alert-info text-center col-xs-12">{$profile->_web_note}</div>
				</div>
			{/if}

			<span class='availableHoldsNoticePlaceHolder'></span>

			<h1>{translate text='Account Settings'}</h1>
			{if $offline}
				<div class="alert alert-warning">{translate text=offline_notice defaultText="<strong>The library system is currently offline.</strong> We are unable to retrieve information about your account at this time."}</div>
			{else}
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

				{include file="MyAccount/switch-linked-user-form.tpl" label="View Contact Information for" actionPath="/MyAccount/ContactInformation"}

				<br>

				<div class="panel-group" id="account-settings-accordion">
					{* ILS Settings *}
					<div class="panel active">
						<a data-toggle="collapse" data-parent="#account-settings-accordion" href="#contactPanel">
							<div class="panel-heading">
								<div class="panel-title">
									{translate text="Contact Information"}
								</div>
							</div>
						</a>
						<div id="contactPanel" class="panel-collapse collapse in">
							<div class="panel-body">
								{if !empty($patronUpdateForm)}
									{$patronUpdateForm}
								{else}
									{* Empty action attribute uses the page loaded. this keeps the selected user patronId in the parameters passed back to server *}
									<form action="" method="post" class="form-horizontal" id="contactUpdateForm">
										<input type="hidden" name="updateScope" value="contact">
										<div class="form-group">
											<div class="col-xs-4"><strong>{translate text='Full Name'}</strong></div>
											<div class="col-xs-8">{$profile->_fullname|escape}</div>
										</div>
										{if !$offline}
											<div class="form-group">
												<div class="col-xs-4"><strong>{translate text='Fines'}</strong></div>
												<div class="col-xs-8">{$profile->_fines|escape}</div>
											</div>
											{if $barcodePin}
											{* Only Display Barcode when the barcode is used as a username and not a password *}
											<div class="form-group">
												<div class="col-xs-4"><strong>{translate text='Library Card Number'}</strong></div>
												<div class="col-xs-8">{$profile->cat_username|escape}</div>
											</div>
											{/if}
											<div class="form-group">
												<div class="col-xs-4"><strong>{translate text='Expiration Date'}</strong></div>
												<div class="col-xs-8">{$profile->_expires|escape}</div>
											</div>
										{/if}
										<div class="form-group">
											<div class="col-xs-4"><strong>{translate text='Home Library'}</strong></div>
											<div class="col-xs-8">{$profile->_homeLocation|escape}</div>
										</div>
										{if !$offline}
											{* Don't show inputs for the Horizon ILS as updating those account settings has not been implemented in the Horizon Driver. *}
											<div class="form-group">
												<div class="col-xs-4">
													<label for="address1">{translate text='Address'}</label>
												</div>
												<div class="col-xs-8">
													{if $edit && $canUpdateContactInfo && $canUpdateAddress && $ils != 'Horizon'}
														<input name="address1" id="address1" value='{$profile->_address1|escape}' size="50" maxlength="75" class="form-control required">
													{elseif $edit && $millenniumNoAddress}
														<input name="address1" id="address1" value='{$profile->_address1|escape}' type="hidden">
														{$profile->_address1|escape}
													{else}
														{$profile->_address1|escape}
													{/if}
												</div>
											</div>
											<div class="form-group">
												<div class="col-xs-4"><label for="city">{translate text='City'}</label></div>
												<div class="col-xs-8">
													{if $edit && $canUpdateContactInfo && $canUpdateAddress && $ils != 'Horizon'}<input name="city" id="city" value="{$profile->_city|escape}" size="50" maxlength="75" class="form-control required">
													{elseif $edit && $millenniumNoAddress}
														<input name="city" id="city" value="{$profile->_city|escape}" type="hidden">
														{$profile->_city|escape}
													{else}{$profile->_city|escape}{/if}
												</div>
											</div>
											<div class="form-group">
												<div class="col-xs-4"><label for="state">{translate text='State'}</label></div>
												<div class="col-xs-8">
													{if $edit && $canUpdateContactInfo && $canUpdateAddress && $ils != 'Horizon'}<input name='state' id="state" value="{$profile->_state|escape}" size="50" maxlength="75" class="form-control required">
													{elseif $edit && $millenniumNoAddress}
														<input name="state" id="state" value="{$profile->_state|escape}" type="hidden">
														{$profile->_state|escape}
													{else}{$profile->_state|escape}{/if}
												</div>
											</div>
											<div class="form-group">
												<div class="col-xs-4"><label for="zip">{translate text='Zip'}</label></div>
												<div class="col-xs-8">
													{if $edit && $canUpdateContactInfo && $canUpdateAddress && $ils != 'Horizon'}
														<input name="zip" id="zip" value="{$profile->_zip|escape}" size="50" maxlength="75" class="form-control required">
													{elseif $edit && $millenniumNoAddress}
														<input name="zip" id="zip" value="{$profile->_zip|escape}" type="hidden">
														{$profile->_zip|escape}
													{else}{$profile->_zip|escape}{/if}
												</div>
											</div>
											<div class="form-group">
												<div class="col-xs-4"><label for="phone">{translate text='Primary Phone Number'}</label></div>
												<div class="col-xs-8">
													{if $edit && $canUpdateContactInfo && ($ils != 'Horizon')}
														<input type="tel" name="phone" id="phone" value="{$profile->phone|replace:'### TEXT ONLY':''|replace:'TEXT ONLY':''|escape}" size="50" maxlength="75" class="form-control">
													{else}
														{$profile->phone|escape}
													{/if}
												</div>
											</div>
											{if $showWorkPhoneInProfile}
												<div class="form-group">
													<div class="col-xs-4"><label for="workPhone">{translate text='Work Phone Number'}</label></div>
													<div class="col-xs-8">{if $edit && $canUpdateContactInfo && $ils != 'Horizon'}<input name="workPhone" id="workPhone" value="{$profile->workPhone|escape}" size="50" maxlength="75" class="form-control">{else}{$profile->workPhone|escape}{/if}</div>
												</div>
											{/if}
										{/if}
										<div class="form-group">
											<div class="col-xs-4"><label for="email">{translate text='Email'}</label></div>
											<div class="col-xs-8">
												{if $edit == true && $canUpdateContactInfo == true}<input type="text" name="email" id="email" value="{$profile->email|escape}" size="50" maxlength="75" class="form-control multiemail">{else}{$profile->email|escape}{/if}
												{* Multiemail class is for form validation; type has to be text for multiemail validation to work correctly *}
											</div>
										</div>
										{if $showPickupLocationInProfile}
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
										{/if}

										{if $showNoticeTypeInProfile}
											<p class="alert alert-info">
												{translate text='account_profile_notification_notice'}
											</p>

										{if $ils != 'CarlX'}
											<div class="form-group">
												<div class="col-xs-4"><strong>{translate text='Receive notices by'}:</strong></div>
												<div class="col-xs-8">
													{if $edit == true && $canUpdateContactInfo == true}
														<div class="btn-group btn-group-sm" data-toggle="buttons">
															{if $treatPrintNoticesAsPhoneNotices}
																	{* Tell the User the notice is Phone even though in the ILS it will be print *}
																	{* MDN 2/24/2016 - If the user changes their notice preference, make it phone to be more accurate, but show as selected if either print or mail is shown *}
																	<label for="sendEmail" class="btn btn-sm btn-default {if $profile->_notices == 'a'}active{/if}"><input type="radio" value="p" id="sendEmail" name="notices" {if $profile->_notices == 'a' || $profile->_notices == 'p'}checked="checked"{/if}> {translate text="Telephone"}</label>
															{else}
																	<label for="noticesMail" class="btn btn-sm btn-default {if $profile->_notices == 'a'}active{/if}"><input type="radio" value="a" id="noticesMail" name="notices" {if $profile->_notices == 'a'}checked="checked"{/if}> {translate text="Postal Mail"}</label>
																	<label for="noticesTel" class="btn btn-sm btn-default {if $profile->_notices == 'p'}active{/if}"><input type="radio" value="p" id="noticesTel" name="notices" {if $profile->_notices == 'p'}checked="checked"{/if}> {translate text="Telephone"}</label>
															{/if}
															<label for="noticesEmail" class="btn btn-sm btn-default {if $profile->_notices == 'z'}active{/if}"><input type="radio" value="z" id="noticesEmail" name="notices" {if $profile->_notices == 'z'}checked="checked"{/if}> {translate text="Email"}</label>
														</div>
													{else}
														{$profile->_noticePreferenceLabel|escape}
													{/if}
												</div>
											</div>
										{/if}

										{if $ils == 'CarlX'} {* CarlX Notification Options *}
											<div class="form-group">
												<div class="col-xs-4"><strong>{translate text='Email notices'}</strong></div>
												<div class="col-xs-8">
													{if $edit == true && $canUpdateContactInfo == true}
														<div class="btn-group btn-group-sm" data-toggle="buttons">
															<label for="sendEmail" class="btn btn-sm btn-default {if $profile->_notices == 'send email'}active{/if}"><input type="radio" value="send email" id="sendEmail" name="notices" {if $profile->_notices == 'send email'}checked="checked"{/if}> {translate text="Send Email"}</label>
															<label for="dontSendEmail" class="btn btn-sm btn-default {if $profile->_notices == 'do not send email'}active{/if}"><input type="radio" value="do not send email" id="dontSendEmail" name="notices" {if $profile->_notices == 'do not send email'}checked="checked"{/if}> {translate text="Do not send email"}</label>
															<label for="optOut" class="btn btn-sm btn-default {if $profile->_notices == 'opted out'}active{/if}"><input type="radio" value="opted out" id="optOut" name="notices" {if $profile->_notices == 'opted out'}checked="checked"{/if}> {translate text="Opt-out"}</label>
														</div>
													{else}
														{$profile->_notices}
													{/if}
												</div>
											</div>


											<div class="form-group">
												<div class="col-xs-4"><label for="emailReceiptFlag" class="control-label">{translate text='Email receipts for checkouts and renewals'}:</label></div>
												<div class="col-xs-8">
													{if $edit == true}
														<input type="checkbox" name="emailReceiptFlag" id="emailReceiptFlag" {if $profile->_emailReceiptFlag==1}checked='checked'{/if} data-switch="">
													{else}
														{if $profile->_emailReceiptFlag==0}{translate text="No"}{else}{translate text="Yes"}{/if}
													{/if}
												</div>
											</div>

											<div class="form-group">
												<div class="col-xs-4"><label for="phoneType" class="">{translate text='Phone Carrier for SMS notices'}</label></div>
												<div class="col-xs-8">
													{if $edit == true && $canUpdateContactInfo == true}
														<select name="phoneType" id="phoneType" class="form-control">
															{if count($phoneTypes) > 0}
																{foreach from=$phoneTypes item=phoneTypeLabel key=phoneType}
																	<option value="{$phoneType}" {if $phoneType == $profile->_phoneType}selected="selected"{/if}>{$phoneTypeLabel|translate}</option>
																{/foreach}
															{else}
																<option></option>
															{/if}
														</select>
													{else}
														{assign var=i value=$profile->_phoneType}
														{$phoneTypes[$i]}
													{/if}
												</div>
											</div>

											<div class="form-group">
												<div class="col-xs-4"><label for="availableHoldNotice" class="control-label">{translate text='SMS notices for available holds'}</label></div>
												<div class="col-xs-8">
													{if $edit == true}
														<input type="checkbox" name="availableHoldNotice" id="availableHoldNotice" {if $profile->_availableHoldNotice==1}checked='checked'{/if} data-switch="">
													{else}
														{if $profile->_availableHoldNotice==0}{translate text="No"}{else}{translate text="Yes"}{/if}
													{/if}
												</div>
											</div>

											<div class="form-group">
												<div class="col-xs-4"><label for="comingDueNotice" class="control-label">{translate text='SMS notices for due date reminders'}</label></div>
												<div class="col-xs-8">
													{if $edit == true}
														<input type="checkbox" name="comingDueNotice" id="comingDueNotice" {if $profile->_comingDueNotice==1}checked='checked'{/if} data-switch="">
													{else}
														{if $profile->_comingDueNotice==0}No{else}Yes{/if}
													{/if}
												</div>
											</div>

											{/if}
										{/if}

										{if $showSMSNoticesInProfile}
											<div class="form-group">
												<div class="col-xs-4"><label for="smsNotices">{translate text='Receive SMS/Text Messages'}</label></div>
												<div class="col-xs-8">
													{if $edit == true && $canUpdateContactInfo == true}
														<input type="checkbox" name="smsNotices" id="smsNotices" {if $profile->_mobileNumber}checked='checked'{/if} data-switch="">
														<p class="help-block alert alert-warning">
															SMS/Text Messages are sent <strong>in addition</strong> to postal mail/email/phone alerts. <strong>Message and data rates may apply.</strong>
															<br><br>
															To sign up for SMS/Text messages, you must opt-in above and enter your Mobile (cell phone) number below.
															<br><br>
															<a href="/Help/Home?topic=smsTerms" data-title="SMS Notice Terms" class="modalDialogTrigger">{translate text="View Terms and Conditions"}</a>
														</p>
													{else}

													{/if}
												</div>
											</div>
											<div class="form-group">
												<div class="col-xs-4"><label for="mobileNumber">{translate text='Mobile Number'}</label></div>
												<div class="col-xs-8">
													{if $edit == true && $canUpdateContactInfo == true}
														<input type="tel" name="mobileNumber" id="mobileNumber" value="{$profile->_mobileNumber}" class="form-control">
													{else}
														{$profile->_mobileNumber}
													{/if}
												</div>
											</div>
										{/if}

										{if !$offline && $edit == true && $canUpdateContactInfo}
											<div class="form-group">
												<div class="col-xs-8 col-xs-offset-4">
													<button type="submit" name="updateContactInfo" class="btn btn-sm btn-primary">{translate text="Update Contact Information"}</button>
												</div>
											</div>
										{/if}
										<script type="text/javascript">
											$("#contactUpdateForm").validate(
											{if $libraryName == 'Arlington Public Library'}{literal}
												{
													rules: {
														phone: {
															minlength: 10
														}
													},
													messages: {
														phone: {
															digits: 'Please use numbers only.',
															minlength: 'Please provide a 10 digit phone number.'
														}
													}
												}
											{/literal}{/if}
											);
										</script>
									</form>
								{/if}
							</div>
						</div>
					</div>

					{if $allowPinReset && !$offline}
						<div class="panel active">
							<a data-toggle="collapse" data-parent="#account-settings-accordion" href="#pinPanel">
								<div class="panel-heading">
									<div class="panel-title">
										{$passwordLabel|translate}
									</div>
								</div>
							</a>
							<div id="pinPanel" class="panel-collapse collapse in">
								<div class="panel-body">

									{* Empty action attribute uses the page loaded. this keeps the selected user patronId in the parameters passed back to server *}
									<form action="" method="post" class="form-horizontal" id="pinForm">
										<input type="hidden" name="updateScope" value="pin">
										<div class="form-group">
											<div class="col-xs-4"><label for="pin" class="control-label">{translate text='Old %1%' 1=$passwordLabel}</label></div>
											<div class="col-xs-8">
												<input type="password" name="pin" id="pin" value="" minlength="{$pinValidationRules.minLength}" maxlength="30" class="form-control required {if $pinValidationRules.onlyDigitsAllowed}digits{/if}">
											</div>
										</div>
										<div class="form-group">
											<div class="col-xs-4"><label for="pin1" class="control-label">{translate text='New %1%' 1=$passwordLabel}</label></div>
											<div class="col-xs-8">
												<input type="password" name="pin1" id="pin1" value="" minlength="{$pinValidationRules.minLength}" maxlength="{$pinValidationRules.maxLength}" class="form-control required {if $pinValidationRules.onlyDigitsAllowed}digits{/if}">
											</div>
										</div>
										<div class="form-group">
											<div class="col-xs-4"><label for="pin2" class="control-label">{translate text='Re-enter New %1%' 1=$passwordLabel}</label></div>
											<div class="col-xs-8">
													<input type="password" name="pin2" id="pin2" value="" minlength="{$pinValidationRules.minLength}" maxlength="{$pinValidationRules.maxLength}" class="form-control required {if $pinValidationRules.onlyDigitsAllowed}digits{/if}">
											</div>
										</div>
										<div class="form-group">
											<div class="col-xs-8 col-xs-offset-4">
												<button type="submit" name="update" class="btn btn-primary">{translate text="Update"}</button>
											</div>
										</div>
										<script type="text/javascript">
											{* input classes  'required', 'digits' are validation rules for the validation plugin *}
											{literal}
											$("#pinForm").validate({
												rules: {
													pin2: {
														equalTo: "#pin1"
													}
												}
											});
											{/literal}
										</script>
									</form>
								</div>
							</div>
						</div>
					{/if}

					{*OverDrive Options*}
					<div class="panel active">
						<a data-toggle="collapse" data-parent="#account-settings-accordion" href="#overdrivePanel">
							<div class="panel-heading">
								<div class="panel-title">
									{translate text="OverDrive Options"}
								</div>
							</div>
						</a>
						<div id="overdrivePanel" class="panel-collapse collapse in">
							<div class="panel-body">
								{* Empty action attribute uses the page loaded. this keeps the selected user patronId in the parameters passed back to server *}
								<form action="" method="post" class="form-horizontal">
									<input type="hidden" name="updateScope" value="overdrive">
									<div class="form-group">
										<div class="col-xs-4"><label for="overdriveEmail" class="control-label">{translate text='OverDrive Hold email'}</label></div>
										<div class="col-xs-8">
											{if $edit == true}<input name="overdriveEmail" id="overdriveEmail" class="form-control" value='{$profile->overdriveEmail|escape}' size='50' maxlength='75'>{else}{$profile->overdriveEmail|escape}{/if}
										</div>
									</div>
									<div class="form-group">
										<div class="col-xs-4"><label for="promptForOverdriveEmail" class="control-label">{translate text='Prompt for OverDrive email'}</label></div>
										<div class="col-xs-8">
											{if $edit == true}
												<input type="checkbox" name="promptForOverdriveEmail" id="promptForOverdriveEmail" {if $profile->promptForOverdriveEmail==1}checked='checked'{/if} data-switch="">
											{else}
												{if $profile->promptForOverdriveEmail==0}No{else}Yes{/if}
											{/if}
										</div>
									</div>
									<p class="help-block alert alert-warning">
										{$overdrivePreferencesNotice}
									</p>
									{if !$offline && $edit == true}
										<div class="form-group">
											<div class="col-xs-8 col-xs-offset-4">
												<button type="submit" name="updateOverDrive" class="btn btn-sm btn-primary">{translate text="Update OverDrive Options"}</button>
											</div>
										</div>
									{/if}
								</form>
							</div>
						</div>
					</div>

					{*Hoopla Options*}
					{if $profile->isValidForEContentSource('hoopla')}
					<div class="panel active">
						<a data-toggle="collapse" data-parent="#account-settings-accordion" href="#hooplaPanel">
							<div class="panel-heading">
								<div class="panel-title">
									{translate text="Hoopla Options"}
								</div>
							</div>
						</a>
						<div id="hooplaPanel" class="panel-collapse collapse in">
							<div class="panel-body">
								{* Empty action attribute uses the page loaded. this keeps the selected user patronId in the parameters passed back to server *}
								<form action="" method="post" class="form-horizontal">
									<input type="hidden" name="updateScope" value="hoopla">
									<div class="form-group">
										<div class="col-xs-4"><label for="hooplaCheckOutConfirmation" class="control-label">{translate text='Ask for confirmation before checking out from Hoopla'}:</label></div>
										<div class="col-xs-8">
											{if $edit == true}
												<input type="checkbox" name="hooplaCheckOutConfirmation" id="hooplaCheckOutConfirmation" {if $profile->hooplaCheckOutConfirmation==1}checked='checked'{/if} data-switch="">
											{else}
												{if $profile->hooplaCheckOutConfirmation==0}No{else}Yes{/if}
											{/if}
										</div>
									</div>
									{if !$offline && $edit == true}
										<div class="form-group">
											<div class="col-xs-8 col-xs-offset-4">
												<button type="submit" name="updateHoopla" class="btn btn-sm btn-primary">{translate text="Update Hoopla Options"}</button>
											</div>
										</div>
									{/if}
								</form>
							</div>
						</div>
					</div>
					{/if}

					{*User Preference Options*}
					{if $showAlternateLibraryOptions || $userIsStaff || ($showRatings && $showComments)}
					<div class="panel active">
						<a data-toggle="collapse" data-parent="#account-settings-accordion" href="#userPreferencePanel">
							<div class="panel-heading">
								<div class="panel-title">
									{translate text="My Preferences"}
								</div>
							</div>
						</a>
						<div id="userPreferencePanel" class="panel-collapse collapse in">
							<div class="panel-body">
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
										<div class="form-group">
											<div class="col-xs-4"><label for="myLocation2" class="control-label">{translate text='My Second Alternate Library'}</label></div>
											<div class="col-xs-8">{if $edit == true}{html_options name="myLocation2" id="myLocation2" class="form-control" options=$locationList selected=$profile->myLocation2Id}{else}{$profile->_myLocation2|escape}{/if}</div>
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
							</div>
						</div>
					</div>
					{/if}


					{* Display user roles if the user has any roles*}
					{if $userIsStaff || count($profile->roles) > 0}
						<div class="panel active">
							<a data-toggle="collapse" data-parent="#account-settings-accordion" href="#rolesPanel">
								<div class="panel-heading">
									<div class="panel-title">
										{translate text="Staff Settings"}
									</div>
								</div>
							</a>
							<div id="rolesPanel" class="panel-collapse collapse in">
								<div class="panel-body">

									<div class="row">
										<div class="col-tn-12 lead">{translate text="Roles"}</div>
									</div>
									<div class="row">
										<div class="col-tn-12">
											<ul>
												{foreach from=$profile->roles item=role}
													<li>{$role}</li>
												{/foreach}
											</ul>
										</div>
									</div>

									<form action="" method="post" class="form-horizontal" id="staffSettingsForm">
										<input type="hidden" name="updateScope" value="staffSettings">

										{if $userIsStaff}
											<div class="row">
												<div class="col-tn-12 lead">{translate text="Staff Auto Logout Bypass"}</div>
											</div>
											<div class="form-group row">
												<div class="col-xs-4"><label for="bypassAutoLogout" class="control-label">{translate text='Bypass Automatic Logout'}</label></div>
												<div class="col-xs-8">
													{if $edit == true}
														<input type="checkbox" name="bypassAutoLogout" id="bypassAutoLogout" {if $profile->bypassAutoLogout==1}checked='checked'{/if} data-switch="">
													{else}
														{if $profile->bypassAutoLogout==0}{translate text="No"}{else}{translate text="Yes"}{/if}
													{/if}
												</div>
											</div>
										{/if}

										{if $profile->hasRole('library_material_requests') && ($materialRequestType == 1)}
											<div class="row">
												<div class="lead col-tn-12">{translate text="Materials Request Management"}</div>
											</div>
											<div class="form-group row">
												<div class="col-xs-4">
													<label for="materialsRequestReplyToAddress" class="control-label">{translate text="Reply-To Email Address"}</label>
												</div>
												<div class="col-xs-8">
													{if $edit == true}
														<input type="text" id="materialsRequestReplyToAddress" name="materialsRequestReplyToAddress" class="form-control multiemail" value="{$user->materialsRequestReplyToAddress}">
													{else}
														{$user->materialsRequestReplyToAddress}
													{/if}
												</div>
											</div>
											<div class="form-group row">
												<div class="col-xs-4">
													<label for="materialsRequestEmailSignature" class="control-label">{translate text="Email Signature"}</label>
												</div>
												<div class="col-xs-8">
													{if $edit == true}
														<textarea id="materialsRequestEmailSignature" name="materialsRequestEmailSignature" class="form-control">{$user->materialsRequestEmailSignature}</textarea>
													{else}
														{$user->materialsRequestEmailSignature}
													{/if}
												</div>
											</div>
										{/if}


										{if !$offline && $edit == true}
											<div class="form-group">
												<div class="col-xs-8 col-xs-offset-4">
													<button type="submit" name="updateStaffSettings" class="btn btn-sm btn-primary">{translate text="Update My Staff Settings"}</button>
												</div>
											</div>
										{/if}
									</form>
								</div>
							</div>
						</div>
					{/if}
				</div>

				<script type="text/javascript">
					{* Initiate any checkbox with a data attribute set to data-switch=""  as a bootstrap switch *}
					{literal}
					$(function(){ $('input[type="checkbox"][data-switch]').bootstrapSwitch()});
					{/literal}
				</script>
			{/if}
		{else}
			<div class="page">
				You must login to view this information. Click <a href="/MyAccount/Login">here</a> to login.
			</div>
		{/if}
	</div>
{/strip}
