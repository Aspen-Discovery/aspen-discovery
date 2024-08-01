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
	<form method="post" action="/MyAccount/MessagingSettings" name="kohaMessaging">
		<input type="hidden" name="modify" value="yes">

		<table class="table table-bordered table-condensed table-striped" id="messagingTable">
			<thead>
			<tr>
				<th>&nbsp;</th>
				<th>{translate text="Days in advance" isPublicFacing=true}</th>
				{if !empty($enableSmsMessaging)}
				<th>{translate text="SMS" isPublicFacing=true}</th>
				{/if}
				{if !empty($enablePhoneMessaging)}
				<th>{translate text="Phone" isPublicFacing=true}</th>
				{/if}
				<th>{translate text="Email" isPublicFacing=true}</th>
				<th>{translate text="Digests only" isPublicFacing=true} <i id="info_digests" data-toggle="tooltip" title="" data-placement="right" class="fa fa-info-circle" data-original-title="{translate text="You can ask for a digest to reduce the number of messages. Messages will be saved and sent as a single message." inAttribute="true" isPublicFacing=true}"></i></th>
				{if !empty($canSave)}
				<!-- <th>RSS</th> --><th>{translate text="Do not notify" isPublicFacing=true}</th>
				{/if}
			</tr>
			</thead>
			<tbody>

			{foreach from=$messageAttributes item=messageType}
				{assign var=messageTypeId value=$messageType.message_attribute_id}
				<tr id="messageType{$messageTypeId}Row">
					<td>
						{translate text=$messageType.label isPublicFacing=true}
					</td>
					<td>
						{if ($messageType.takes_days)}
							{if !empty($canSave)}
								<select class="form-control-sm" name="{$messageTypeId}-DAYS" aria-label="Number of days for {$messageType.label}">
									{foreach from=$validNoticeDays item="i"}
										<option value="{$i}" {if $messagingSettings.$messageTypeId.daysInAdvance == $i}selected="selected"{/if}>{$i}</option>
									{/foreach}
								</select>
							{else}
								{$messagingSettings.$messageTypeId.daysInAdvance}
							{/if}
						{else}
							-
						{/if}
					</td>
					{if !empty($enableSmsMessaging)}
						<td>
						{if !empty($messagingSettings.$messageTypeId) && !empty($messagingSettings.$messageTypeId.allowableTransports.sms)}
							{if !empty($canSave)}
								<input type="checkbox" id="sms{$messageTypeId}" name="{$messageTypeId}[]" value="sms" aria-label="Send SMS Message for {$messageType.label}" onclick="$('#none{$messageTypeId}').attr('checked', false); return AspenDiscovery.Account.toggleKohaDigestCheckbox()" {if !empty($messagingSettings.$messageTypeId) && !empty($messagingSettings.$messageTypeId.selectedTransports.sms)}checked="checked"{/if}>
							{else}
								{if $messagingSettings.$messageTypeId.selectedTransports.sms} {translate text='Yes' isPublicFacing=true}{else} {translate text='No' isPublicFacing=true}{/if}
							{/if}
						{else}
							-
						{/if}
						</td>
					{/if}
					{if !empty($enablePhoneMessaging)}
						<td>
						{if !empty($messagingSettings.$messageTypeId) && !empty($messagingSettings.$messageTypeId.allowableTransports.phone)}
							{if !empty($canSave)}
								<input type="checkbox" id="phone{$messageTypeId}" name="{$messageTypeId}[]" value="phone" aria-label="Receive Phone Call for {$messageType.label}" onclick="$('#none{$messageTypeId}').attr('checked', false); return AspenDiscovery.Account.toggleKohaDigestCheckbox()" {if !empty($messagingSettings.$messageTypeId) && !empty($messagingSettings.$messageTypeId.selectedTransports.phone)}checked="checked"{/if}>
							{else}
								{if $messagingSettings.$messageTypeId.selectedTransports.phone} {translate text='Yes' isPublicFacing=true}{else} {translate text='No' isPublicFacing=true}{/if}
							{/if}
						{else}
							-
						{/if}
						</td>
					{/if}
					<td>
					{if !empty($messagingSettings.$messageTypeId) && !empty($messagingSettings.$messageTypeId.allowableTransports.email)}
						{if !empty($canSave)}
							<input type="checkbox" id="email{$messageTypeId}" name="{$messageTypeId}[]" value="email" aria-label="Send Email for {$messageType.label}" onclick="$('#none{$messageTypeId}').attr('checked', false); return AspenDiscovery.Account.toggleKohaDigestCheckbox()" {if !empty($messagingSettings.$messageTypeId) && !empty($messagingSettings.$messageTypeId.selectedTransports.email)}checked="checked"{/if}>
						{else}
							{if $messagingSettings.$messageTypeId.selectedTransports.email} {translate text='Yes' isPublicFacing=true}{else} {translate text='No' isPublicFacing=true}{/if}
						{/if}
					{else}
						-
					{/if}
					</td>
					<td>
						{if !empty($messagingSettings.$messageTypeId) && !empty($messagingSettings.$messageTypeId.allowDigests)}
							{if !empty($canSave)}
								<input type="checkbox" id="digest{$messageTypeId}" value="{$messageTypeId}" name="digest{$messageTypeId}" data-allowDigests="{$messagingSettings.$messageTypeId.allowDigests}" aria-label="Send Message for {$messageType.label} as digest" {if !empty($messagingSettings.$messageTypeId) && !empty($messagingSettings.$messageTypeId.wantsDigest)}checked="checked"{else}disabled{/if}>
							{else}
								{if  $messagingSettings.$messageTypeId.allowDigests} {translate text='Yes' isPublicFacing=true}{else} {translate text='No' isPublicFacing=true}{/if}
							{/if}
						{else}
							-
						{/if}
					</td>
					{if !empty($canSave)}
						<td>
							<input type="checkbox" class="none" id="none{$messageTypeId}" aria-label="Send No Message for {$messageType.label}">
						</td>
					{/if}
				</tr>
			{/foreach}

			</tbody>
		</table>

		{if !empty($enableSmsMessaging)}
			<div id="smsNoticeRow">
				<p class="help-block alert alert-info">
					<span class="glyphicon glyphicon-info-sign" aria-hidden="true"></span> {translate text="Some charges for text messages may be incurred when using this service. Please check with your mobile service provider if you have questions." isPublicFacing=true}
				</p>
			</div>
			<div class="form-group propertyRow" id="smsNumberRow">
				<label for="SMSnumber" class="control-label">{translate text="SMS number" isPublicFacing=true}</label>
				<input type="text" id="SMSnumber" name="SMSnumber" value="{$smsAlertNumber}" class="form-control" {if empty($canSave)}readonly{/if}>
				<span id="helpBlock_SMSnumber" class="help-block">
				{translate text="Please enter numbers only. <b>(123) 456-7890</b> would be entered as <b>1234567890</b>." isPublicFacing=true}
				</span>
			</div>
			<div class="form-group propertyRow" id="smsProviderRow">
				<label for="sms_provider_id" class="control-label">{translate text="SMS provider" isPublicFacing=true}</label>
				<select id="sms_provider_id" name="sms_provider_id" class="form-control" {if empty($canSave)}readonly{/if}>
					<option value="">{translate text="Unknown" isPublicFacing=true}</option>
					{foreach from=$smsProviders item=provider key=id}
					<option value="{$id}" {if $smsProviderId==$id}selected="selected"{/if}>{$provider}</option>
					{/foreach}
				</select>
				<span id="helpBlock_sms_provider_id" class="help-block">
				{translate text="Please contact a library staff member if you are unsure of your mobile service provider, or you do not see your provider in this list." isPublicFacing=true}
				</span>
			</div>
		{/if}
		{if !empty($shoutbombAttribute)}
			<div class="form-group propertyRow" id="shoutbombRow">
				<label for="borrower_attribute_SHOUTBOMB" class="control-label">{translate text=$shoutbombAttribute.desc isPublicFacing=true}</label>
				<select id="borrower_attribute_SHOUTBOMB" name="borrower_attribute_SHOUTBOMB" class="form-control" {if empty($canSave)}readonly{/if}>
					{foreach from=$shoutbombAttribute.authorized_values item=label key=value}
						<option value="{$value}" {if $profile->borrower_attribute_SHOUTBOMB==$value}selected="selected"{/if}>{$label}</option>
					{/foreach}
				</select>
			</div>
		{/if}
		{if $canTranslateNotices}
			<div class="form-group propertyRow" id="langRow">
                <label for="lang" class="control-label">{translate text="Preferred language for notices" isPublicFacing=true}</label>
                {if !empty($canSave) && !empty($noticeLanguages)}
	            <select class="form-control" name="lang" aria-label="{translate text="Preferred language for notices" isPublicFacing=true}">
                    {foreach from=$noticeLanguages item="language" key=id}
                        <option value="{$id}" {if $preferredNoticeLanguage==$id}selected="selected"{/if}>{$language}</option>
                    {/foreach}
                </select>
                {/if}
	        </div>
		{/if}
		{if !empty($canSave)}
			<div class="form-group propertyRow" id="submitRow">
				<button type="submit" class="btn btn-sm btn-primary" name="submit">{translate text="Update Settings" isPublicFacing=true}</button>
			</div>
		{/if}
	</form>
</div>
{literal}
<script type="application/javascript">
$(document).ready(function(){
	$(".none").click(function(){
		if($(this).prop("checked")){
			var rowId = $(this).attr("id");
			var newId = Number(rowId.replace("none",""));
			$("#sms"+newId).removeAttr("checked");
			$("#phone"+newId).removeAttr("checked");
			$("#email"+newId).removeAttr("checked");
			$("#digest"+newId).removeAttr("checked");
			$("#rss"+newId).removeAttr("checked");
		}
	});
	AspenDiscovery.Account.toggleKohaDigestCheckbox();
});
</script>
{/literal}