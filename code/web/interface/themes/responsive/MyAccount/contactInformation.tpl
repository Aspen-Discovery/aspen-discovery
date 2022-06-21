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
			{if !empty($ilsMessages)}
				{include file='ilsMessages.tpl' messages=$ilsMessages}
			{/if}

			<h1>{translate text='Contact Information' isPublicFacing=true}</h1>
			{if $offline}
				<div class="alert alert-warning"><strong>{translate text="The library system is currently offline." isPublicFacing=true}</strong> {translate text="We are unable to retrieve information about your account at this time." isPublicFacing=true}</div>
			{else}
{* MDN 7/26/2019 Do not allow access to contact information for linked users *}
{*				{include file="MyAccount/switch-linked-user-form.tpl" label="View Contact Information for" actionPath="/MyAccount/ContactInformation"}*}

				{if !empty($profileUpdateErrors)}
					<div class="alert alert-danger">{$profileUpdateErrors}</div>
				{/if}
				{if !empty($profileUpdateMessage)}
					<div class="alert alert-success">{$profileUpdateMessage}</div>
				{/if}

				{if !empty($patronUpdateForm)}
					{$patronUpdateForm}
				{else}
					{* Empty action attribute uses the page loaded. this keeps the selected user patronId in the parameters passed back to server *}
					<form action="" method="post" class="form-horizontal" id="contactUpdateForm">
						<input type="hidden" name="updateScope" value="contact">
						<div class="form-group">
							<div class="col-xs-4"><strong>{translate text='Full Name' isPublicFacing=true}</strong></div>
							<div class="col-xs-8">{$profile->_fullname|escape}</div>
						</div>
						{if !$offline}
							{if $barcodePin}
							{* Only Display Barcode when the barcode is used as a username and not a password *}
							<div class="form-group">
								<div class="col-xs-4"><strong>{translate text='Library Card Number' isPublicFacing=true}</strong></div>
								<div class="col-xs-8">{$profile->cat_username|escape}</div>
							</div>
							{/if}
							<div class="form-group">
								<div class="col-xs-4"><strong>{translate text='Expiration Date' isPublicFacing=true}</strong></div>
								<div class="col-xs-8">{$profile->_expires|escape}</div>
							</div>
						{/if}
						{if !$offline}
							{* Don't show inputs for the Horizon ILS as updating those account settings has not been implemented in the Horizon Driver. *}
							<div class="form-group">
								<div class="col-xs-4">
									<label for="address1">{translate text='Address' isPublicFacing=true}</label>
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
								<div class="col-xs-4"><label for="city">{translate text='City' isPublicFacing=true}</label></div>
								<div class="col-xs-8">
									{if $edit && $canUpdateContactInfo && $canUpdateAddress && $ils != 'Horizon'}<input name="city" id="city" value="{$profile->_city|escape}" size="50" maxlength="75" class="form-control required">
									{elseif $edit && $millenniumNoAddress}
										<input name="city" id="city" value="{$profile->_city|escape}" type="hidden">
										{$profile->_city|escape}
									{else}{$profile->_city|escape}{/if}
								</div>
							</div>
							<div class="form-group">
								<div class="col-xs-4"><label for="state">{translate text='State' isPublicFacing=true}</label></div>
								<div class="col-xs-8">
									{if $edit && $canUpdateContactInfo && $canUpdateAddress && $ils != 'Horizon'}<input name='state' id="state" value="{$profile->_state|escape}" size="50" maxlength="75" class="form-control required">
									{elseif $edit && $millenniumNoAddress}
										<input name="state" id="state" value="{$profile->_state|escape}" type="hidden">
										{$profile->_state|escape}
									{else}{$profile->_state|escape}{/if}
								</div>
							</div>
							<div class="form-group">
								<div class="col-xs-4"><label for="zip">{translate text='Zip' isPublicFacing=true}</label></div>
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
								<div class="col-xs-4"><label for="phone">{translate text='Primary Phone Number' isPublicFacing=true}</label></div>
								<div class="col-xs-8">
									{if $edit && $canUpdateContactInfo && $canUpdatePhoneNumber && ($ils != 'Horizon')}
										<input type="tel" name="phone" id="phone" value="{$profile->phone|replace:'### TEXT ONLY':''|replace:'TEXT ONLY':''|escape}" size="50" maxlength="75" class="form-control">
									{else}
										{$profile->phone|escape}
									{/if}
								</div>
							</div>
							{if $showWorkPhoneInProfile}
								<div class="form-group">
									<div class="col-xs-4"><label for="workPhone">{translate text='Work Phone Number' isPublicFacing=true}</label></div>
									<div class="col-xs-8">{if $edit && $canUpdateContactInfo && $canUpdatePhoneNumber && $ils != 'Horizon'}<input name="workPhone" id="workPhone" value="{$profile->workPhone|escape}" size="50" maxlength="75" class="form-control">{else}{$profile->workPhone|escape}{/if}</div>
								</div>
							{/if}
						{/if}
						<div class="form-group">
							<div class="col-xs-4"><label for="email">{translate text='Email' isPublicFacing=true}</label></div>
							<div class="col-xs-8">
								{if $edit == true && $canUpdateContactInfo == true}<input type="text" name="email" id="email" value="{$profile->email|escape}" size="50" maxlength="75" class="form-control multiemail">{else}{$profile->email|escape}{/if}
								{* Multiemail class is for form validation; type has to be text for multiemail validation to work correctly *}
							</div>
						</div>
						{if $allowHomeLibraryUpdates}
							<div class="form-group">
								<div class="col-xs-4"><label for="pickupLocation" class="">{translate text='Home Library' isPublicFacing=true}</label></div>
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
								{translate text='The following settings determine how you would like to receive notifications when physical materials are ready for pickup at your library.  Notifications for online content are always delivered via email.' isPublicFacing=true}
							</p>

						{if $ils != 'CarlX'}
							<div class="form-group">
								<div class="col-xs-4"><strong>{translate text='Receive notices by' isPublicFacing=true}</strong></div>
								<div class="col-xs-8">
									{if $edit == true && $canUpdateContactInfo == true}
										<div class="btn-group btn-group-sm" data-toggle="buttons">
											{if $treatPrintNoticesAsPhoneNotices}
												{* Tell the User the notice is Phone even though in the ILS it will be print *}
												{* MDN 2/24/2016 - If the user changes their notice preference, make it phone to be more accurate, but show as selected if either print or mail is shown *}
												<label for="sendEmail" class="btn btn-sm btn-default {if $profile->_notices == 'a'}active{/if}"><input type="radio" value="p" id="sendEmail" name="notices" {if $profile->_notices == 'a' || $profile->_notices == 'p'}checked="checked"{/if}> {translate text="Telephone" isPublicFacing=true}</label>
											{else}
												<label for="noticesMail" class="btn btn-sm btn-default {if $profile->_notices == 'a'}active{/if}"><input type="radio" value="a" id="noticesMail" name="notices" {if $profile->_notices == 'a'}checked="checked"{/if}> {translate text="Postal Mail" isPublicFacing=true}</label>
												<label for="noticesTel" class="btn btn-sm btn-default {if $profile->_notices == 'p'}active{/if}"><input type="radio" value="p" id="noticesTel" name="notices" {if $profile->_notices == 'p'}checked="checked"{/if}> {translate text="Telephone" isPublicFacing=true}</label>
											{/if}
											<label for="noticesEmail" class="btn btn-sm btn-default {if $profile->_notices == 'z'}active{/if}"><input type="radio" value="z" id="noticesEmail" name="notices" {if $profile->_notices == 'z'}checked="checked"{/if}> {translate text="Email" isPublicFacing=true}</label>
										</div>
									{else}
										{$profile->_noticePreferenceLabel|escape}
									{/if}
								</div>
							</div>
						{/if}

						{if $ils == 'CarlX'} {* CarlX Notification Options *}
							<div class="form-group">
								<div class="col-xs-4"><strong>{translate text='Email notices' isPublicFacing=true}</strong></div>
								<div class="col-xs-8">
									{if $edit == true && $canUpdateContactInfo == true}
										<div class="btn-group btn-group-sm" data-toggle="buttons">
											<label for="sendEmail" class="btn btn-sm btn-default {if $profile->_notices == 'send email'}active{/if}"><input type="radio" value="send email" id="sendEmail" name="notices" {if $profile->_notices == 'send email'}checked="checked"{/if}> {translate text="Send Email" isPublicFacing=true}</label>
											<label for="dontSendEmail" class="btn btn-sm btn-default {if $profile->_notices == 'do not send email'}active{/if}"><input type="radio" value="do not send email" id="dontSendEmail" name="notices" {if $profile->_notices == 'do not send email'}checked="checked"{/if}> {translate text="Do not send email" isPublicFacing=true}</label>
											<label for="optOut" class="btn btn-sm btn-default {if $profile->_notices == 'opted out'}active{/if}"><input type="radio" value="opted out" id="optOut" name="notices" {if $profile->_notices == 'opted out'}checked="checked"{/if}> {translate text="Opt-out" isPublicFacing=true}</label>
										</div>
									{else}
										{$profile->_notices}
									{/if}
								</div>
							</div>

							<div class="form-group">
								<div class="col-xs-4"><label for="emailReceiptFlag" class="">{translate text='Email receipts for checkouts and renewals' isPublicFacing=true}</label></div>
								<div class="col-xs-8">
									{if $edit == true}
										<input type="checkbox" name="emailReceiptFlag" id="emailReceiptFlag" {if $profile->_emailReceiptFlag==1}checked='checked'{/if} data-switch="">
									{else}
										{if $profile->_emailReceiptFlag==0}{translate text="No" isPublicFacing=true}{else}{translate text="Yes" isPublicFacing=true}{/if}
									{/if}
								</div>
							</div>

							<div class="form-group">
								<div class="col-xs-4"><label for="phoneType" class="">{translate text='Phone Carrier for SMS notices' isPublicFacing=true}</label></div>
								<div class="col-xs-8">
									{if $edit == true && $canUpdateContactInfo == true}
										<select name="phoneType" id="phoneType" class="form-control">
											{if count($phoneTypes) > 0}
												{foreach from=$phoneTypes item=phoneTypeLabel key=phoneType}
													<option value="{$phoneType}" {if $phoneType == $profile->_phoneType}selected="selected"{/if}>{translate text=$phoneTypeLabel isPublicFacing=true inAttribute=true}</option>
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
								<div class="col-xs-4"><label for="availableHoldNotice" class="control-label">{translate text='SMS notices for available holds' isPublicFacing=true}</label></div>
								<div class="col-xs-8">
									{if $edit == true}
										<input type="checkbox" name="availableHoldNotice" id="availableHoldNotice" {if $profile->_availableHoldNotice==1}checked='checked'{/if} data-switch="">
									{else}
										{if $profile->_availableHoldNotice==0}{translate text="No" isPublicFacing=true}{else}{translate text="Yes" isPublicFacing=true}{/if}
									{/if}
								</div>
							</div>

							<div class="form-group">
								<div class="col-xs-4"><label for="comingDueNotice" class="control-label">{translate text='SMS notices for due date reminders' isPublicFacing=true}</label></div>
								<div class="col-xs-8">
									{if $edit == true}
										<input type="checkbox" name="comingDueNotice" id="comingDueNotice" {if $profile->_comingDueNotice==1}checked='checked'{/if} data-switch="">
									{else}
										{if $profile->_comingDueNotice==0}{translate text="No" isPublicFacing=true}{else}{translate text="Yes" isPublicFacing=true}{/if}
									{/if}
								</div>
							</div>

							{/if}
						{/if}

						{if $showSMSNoticesInProfile}
							<div class="form-group">
								<div class="col-xs-4"><label for="smsNotices">{translate text='Receive SMS/Text Messages' isPublicFacing=true}</label></div>
								<div class="col-xs-8">
									{if $edit == true && $canUpdateContactInfo == true}
										<input type="checkbox" name="smsNotices" id="smsNotices" {if $profile->_mobileNumber}checked='checked'{/if} data-switch="">
										<p class="help-block alert alert-warning">
											SMS/Text Messages are sent <strong>in addition</strong> to postal mail/email/phone alerts. <strong>Message and data rates may apply.</strong>
											<br><br>
											To sign up for SMS/Text messages, you must opt-in above and enter your Mobile (cell phone) number below.
										</p>
									{else}

									{/if}
								</div>
							</div>
							<div class="form-group">
								<div class="col-xs-4"><label for="mobileNumber">{translate text='Mobile Number' isPublicFacing=true}</label></div>
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
									<button type="submit" name="updateContactInfo" class="btn btn-sm btn-primary">{translate text="Update Contact Information" isPublicFacing=true}</button>
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
