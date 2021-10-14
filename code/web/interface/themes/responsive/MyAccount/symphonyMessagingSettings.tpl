<div id="main-content">
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

	<h1>{translate text='Messaging Settings' isPublicFacing=true}</h1>

	{* MDN 7/26/2019 Do not allow access for linked users *}
	{*	{include file="MyAccount/switch-linked-user-form.tpl" label="Viewing Requests for" actionPath="/MyAccount/ReadingHistory"}*}

	{if !empty($result)}
		{if ($result.success)}
			<div class="alert alert-info">{$result.message}</div>
		{else}
			<div class="alert alert-danger">{$result.message}</div>
		{/if}
	{/if}

	{if !empty($error)}
		<div class="alert alert-danger">{$error}</div>
	{else}
		<form method="post" action="/MyAccount/MessagingSettings" name="symphonyMessaging">
			<input type="hidden" name="modify" value="yes">

			<table class="table table-bordered table-condensed table-striped" id="messagingTable">
				<thead>
					<tr>
						<th>{translate text="Phone Label (ex. my cell)" isPublicFacing=true}</th>
						<th>{translate text="Phone Number" isPublicFacing=true}</th>
						<th>{translate text="Country Code" isPublicFacing=true}</th>
						<th>{translate text="Alerts to Receive" isPublicFacing=true}</th>
						<th></th>
					</tr>
				</thead>
				<tbody>

				{foreach from=$phoneList key=phoneIndex item=phone}
					<tr id="phoneRow{$phoneIndex}" {if $phoneIndex != 1 && $phone.enabled == false}style="display: none"{/if}>
						<td>
							<input type="hidden" name="phoneNumberDeleted[{$phoneIndex}]" id="phoneNumberDeleted{$phoneIndex}">
							<input type="hidden" name="phoneNumberKey[{$phoneIndex}]" id="phoneNumberKey{$phoneIndex}" value="{$phone.key}">
                            {if $canSave}<input type="text" class="form-control form-control-sm" name="phoneLabel[{$phoneIndex}]" value="{$phone.label}" aria-label="Phone Label {$phoneIndex}"/>{else}{$phone.label}{/if}
						</td>
						<td>{if $canSave}<input type="text" class="form-control form-control-sm" name="phoneNumber[{$phoneIndex}]" value="{$phone.number}" aria-label="Phone Number {$phoneIndex}"/>{else}{$phone.number}{/if}</td>
						<td>
							{if $canSave}
								<select class="form-control form-control-sm" name="countryCode[{$phoneIndex}]">
									{foreach from=$countryCodes key=countryCode item=displayName}
										<option value="{$countryCode}" {if $countryCode == $phone.countryCode}selected{/if}>{$displayName}</option>
									{/foreach}
								</select>
							{else}
								{$phone.countryCode}
							{/if}
						</td>
						<td>
							<div><label for="billNotices_{$phoneIndex}"><input type="checkbox" name="billNotices[{$phoneIndex}]" id="billNotices_{$phoneIndex}" {if $phone.billNotices}checked{/if}>{translate text="Bill Notices" isPublicFacing=true}</label></div>
							<div><label for="overdueNotices_{$phoneIndex}"><input type="checkbox" name="overdueNotices[{$phoneIndex}]" id="overdueNotices_{$phoneIndex}" {if $phone.overdueNotices}checked{/if}>{translate text="Overdue Notices" isPublicFacing=true}</label></div>
							<div><label for="holdPickupNotices_{$phoneIndex}"><input type="checkbox" name="holdPickupNotices[{$phoneIndex}]" id="overdueNotices_{$phoneIndex}" {if $phone.holdPickupNotices}checked{/if}>{translate text="Hold Pickup Notices" isPublicFacing=true}</label></div>
							<div><label for="manualMessages_{$phoneIndex}"><input type="checkbox" name="manualMessages[{$phoneIndex}]" id="manualMessages_{$phoneIndex}" {if $phone.manualMessages}checked{/if}>{translate text="Manual Messages" isPublicFacing=true}</label></div>
							<div><label for="generalAnnouncements_{$phoneIndex}"><input type="checkbox" name="generalAnnouncements[{$phoneIndex}]" id="generalAnnouncements_{$phoneIndex}" {if $phone.generalAnnouncements}checked{/if}>{translate text="General Announcements" isPublicFacing=true}</label></div>
						</td>
						<td><button class="btn btn-sm btn-danger" onclick="$('#phoneRow{$phoneIndex}').hide();$('#phoneNumberDeleted{$phoneIndex}').val('true');return false;">{translate text="Delete" isPublicFacing=true}</button> </td>
					</tr>
				{/foreach}

				</tbody>
			</table>
			{if $canSave}
				<button class="btn btn-sm btn-primary" name="addPhone" id="addPhoneBtn" onclick="return addPhoneRow();">{translate text="Add Phone Number" isPublicFacing=true}</button>
				<button type="submit" class="btn btn-sm btn-primary" name="submit">{translate text="Update Settings" isPublicFacing=true}</button>
			{/if}
		</form>
    {/if}
</div>
{literal}
<script type="application/javascript">
	var numActivePhoneNumbers = {/literal}{$numActivePhoneNumbers}{literal};
	function addPhoneRow(){
		if (numActivePhoneNumbers < 5) {
			numActivePhoneNumbers++;
			$("#phoneRow" + numActivePhoneNumbers).show();
		}
		if (numActivePhoneNumbers === 5){
			$("#addPhoneBtn").hide();
		}
		return false;
	}
</script>
{/literal}