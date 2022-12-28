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

			<h1>{translate text="Hoopla Options" isPublicFacing=true}</h1>
			{if !empty($offline)}
				<div class="alert alert-warning"><strong>{translate text=$offlineMessage isPublicFacing=true}</strong></div>
			{else}
				{* Empty action attribute uses the page loaded. this keeps the selected user patronId in the parameters passed back to server *}
				<form action="" method="post" class="form-horizontal">
					<input type="hidden" name="updateScope" value="hoopla">
					<div class="form-group">
						<div class="col-xs-6"><label for="hooplaCheckOutConfirmation" class="control-label">{translate text='Ask for confirmation before checking out from Hoopla' isPublicFacing=true}</label></div>
						<div class="col-xs-6">
							{if $edit == true}
								<input type="checkbox" name="hooplaCheckOutConfirmation" id="hooplaCheckOutConfirmation" {if $profile->hooplaCheckOutConfirmation==1}checked='checked'{/if} data-switch="">
							{else}
								{if $profile->hooplaCheckOutConfirmation==0}{translate text="No" isPublicFacing=true}{else}{translate text="Yes" isPublicFacing=true}{/if}
							{/if}
						</div>
					</div>
					{if empty($offline) && $edit == true}
						<div class="form-group">
							<div class="col-xs-6 col-xs-offset-6">
								<button type="submit" name="updateHoopla" class="btn btn-sm btn-primary">{translate text="Update Hoopla Options" isPublicFacing=true}</button>
							</div>
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