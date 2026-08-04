<div id="main-content">
	<h1>{translate text='Messaging Settings' isPublicFacing=true}</h1>

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
		<form method="post" action="/MyAccount/MessagingSettings" name="sierraMessaging" >
			<input type="hidden" name="modify" value="yes">
			<div class="form-group propertyRow">
				<label for="noticePreference">{translate text="Notice Preference" isPublicFacing=true inAttribute=true}</label>
				{if !empty($canSave)}
					<select name="noticePreference" id="noticePreference" class="form-control">
						{foreach from=$notificationOptions item=name key=code}
							<option value="{$code}"{if !empty($name.selected)} selected="selected"{/if}>{translate text=$name.name isPublicFacing=true inAttribute=true}</option>
						{/foreach}
					</select>
				{else}
					{foreach from=$notificationOptions item=name key=code}
						{if !empty($name.selected)}
							{translate text=$name.name isPublicFacing=true inAttribute=true}
						{/if}
					{/foreach}
				{/if}
			</div>

			{if !empty($canSave)}
				<button type="submit" class="btn btn-sm btn-primary" name="submit">{translate text="Update Settings" isPublicFacing=true}</button>
			{/if}
		</form>
	{/if}
</div>
