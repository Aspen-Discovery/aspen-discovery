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

			<h1>{translate text='Staff Settings' isPublicFacing=true}</h1>
			{if !empty($offline)}
				<div class="alert alert-warning"><strong>{translate text=$offlineMessage isPublicFacing=true}</strong></div>
			{else}
{* MDN 7/26/2019 Do not allow access for linked users *}
{*				{include file="MyAccount/switch-linked-user-form.tpl" label="View Account Settings for" actionPath="/MyAccount/StaffSettings"}*}

				{* Display user roles if the user has any roles*}
				{if count($profile->roles) > 0}
					<h2>{translate text="Roles" isPublicFacing=true}</h2>
					<div class="propertyRow">
						<ul>
							{foreach from=$profile->roles item=role}
								<li>{translate text=$role->name isStaffFacing=true isAdminEnteredData=true} - {translate text=$role->description isStaffFacing=true isAdminEnteredData=true}</li>
							{/foreach}
						</ul>
					</div>
				{/if}

				<form action="" method="post" id="staffSettingsForm">
					<input type="hidden" name="updateScope" value="staffSettings">

					{if !empty($userIsStaff)}
						<h2>{translate text="General" isPublicFacing=true}</h2>
						<div class="form-group propertyRow">
							<label for="bypassAutoLogout" class="control-label">{translate text='Bypass Automatic Logout' isPublicFacing=true}</label>&nbsp;
							{if $edit == true}
								<input type="checkbox" name="bypassAutoLogout" id="bypassAutoLogout" {if $profile->bypassAutoLogout==1}checked='checked'{/if} data-switch="">
							{else}
								{if $profile->bypassAutoLogout==0} {translate text='No' isPublicFacing=true}{else} {translate text='Yes' isPublicFacing=true}{/if}
							{/if}
						</div>
					{/if}

					{if $profile->hasPermission('Manage Library Materials Requests') && ($materialRequestType == 1)}
						<h2>{translate text="Materials Request Management" isPublicFacing=true}</h2>
						{if $sendEmailOnAssignmentForLibrary}
							<div class="form-group propertyRow">
								<label for="materialsRequestSendEmailOnAssign" class="control-label">{translate text="Receive email when a Materials Request is assigned to me" isPublicFacing=true}</label>&nbsp;
								{if $edit == true}
									<input type="checkbox" name="materialsRequestSendEmailOnAssign" id="materialsRequestSendEmailOnAssign" {if $user->materialsRequestSendEmailOnAssign==1}checked='checked'{/if} data-switch="">
								{else}
									{if $user->materialsRequestSendEmailOnAssign == 0} {translate text='No' isPublicFacing=true}{else} {translate text='Yes' isPublicFacing=true}{/if}
								{/if}
							</div>
						{/if}
						<div class="form-group propertyRow">
							<label for="materialsRequestReplyToAddress" class="control-label">{translate text="Reply-To Email Address" isPublicFacing=true}</label>
							{if $edit == true}
								<input type="text" id="materialsRequestReplyToAddress" name="materialsRequestReplyToAddress" class="form-control multiemail" value="{$user->materialsRequestReplyToAddress}">
							{else}
								{$user->materialsRequestReplyToAddress}
							{/if}
						</div>
						<div class="form-group propertyRow">
							<label for="materialsRequestEmailSignature" class="control-label">{translate text="Email Signature" isPublicFacing=true}</label>
							{if $edit == true}
								<textarea id="materialsRequestEmailSignature" name="materialsRequestEmailSignature" class="form-control">{$user->materialsRequestEmailSignature}</textarea>
							{else}
								{$user->materialsRequestEmailSignature}
							{/if}
						</div>
					{/if}


					{if empty($offline) && $edit == true}
						<div class="form-group">
							<button type="submit" name="updateStaffSettings" class="btn btn-sm btn-primary">{translate text="Update Settings" isPublicFacing=true}</button>
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