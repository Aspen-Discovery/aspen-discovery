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

			<h1>{translate text='Reset Username' isPublicFacing=true}</h1>
			{if $offline}
				<div class="alert alert-warning"><strong>{translate text=$offlineMessage isPublicFacing=true}</strong></div>
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
				<div class="alert alert-info">
					{translate text="Usernames must be between %1% and %2% characters." isPublicFacing=true 1=$usernameValidationRules.minLength 2=$usernameValidationRules.maxLength}
					{if !empty($usernameValidationRules.additionalRequirements)}
						<br/>
						{$usernameValidationRules.additionalRequirements}
					{/if}
				</div>

				{* Empty action attribute uses the page loaded. this keeps the selected user patronId in the parameters passed back to server *}
				<form action="" method="post" class="form-horizontal" id="usernameForm">
					<div class="form-group">
						<div class="col-xs-4"><label for="username" class="control-label">{translate text='Username' isPublicFacing=true}</label></div>
						<div class="col-xs-8">
							<input type="text" name="username" id="username" value="" size="{$usernameValidationRules.minLength}" maxlength="{$usernameValidationRules.maxLength}" class="form-control required" autocomplete="false">
						</div>
					</div>
					<div class="form-group">
						<div class="col-xs-8 col-xs-offset-4">
							<button type="submit" name="submit" class="btn btn-primary">{translate text="Update" isPublicFacing=true}</button>
						</div>
					</div>
				</form>
			{/if}
		{else}
			<div class="page">
				{translate text="You must sign in to view this information." isPublicFacing=true}<a href='/MyAccount/Login' class="btn btn-primary">{translate text="Sign In" isPublicFacing=true}</a>
			</div>
		{/if}
	</div>
{/strip}
