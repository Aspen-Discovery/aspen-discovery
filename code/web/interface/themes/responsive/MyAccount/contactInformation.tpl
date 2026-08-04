{strip}
	<div id="main-content">
		{if !empty($loggedIn)}

			<h1>{translate text='Contact Information' isPublicFacing=true}</h1>
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
			{if !empty($offline)}
				<div class="alert alert-warning"><strong>{translate text=$offlineMessage isPublicFacing=true}</strong></div>
			{else}
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
					{include file='MyAccount/contactInformationForm.tpl'}
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
