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

			<h1>{translate text="Hoopla Options"}</h1>
			{if $offline}
				<div class="alert alert-warning">{translate text=offline_notice defaultText="<strong>The library system is currently offline.</strong> We are unable to retrieve information about your account at this time."}</div>
			{else}
				{* Empty action attribute uses the page loaded. this keeps the selected user patronId in the parameters passed back to server *}
				<form action="" method="post" class="form-horizontal">
					<input type="hidden" name="updateScope" value="hoopla">
					<div class="form-group">
						<div class="col-xs-6"><label for="hooplaCheckOutConfirmation" class="control-label">{translate text='Ask for confirmation before checking out from Hoopla'}</label></div>
						<div class="col-xs-6">
							{if $edit == true}
								<input type="checkbox" name="hooplaCheckOutConfirmation" id="hooplaCheckOutConfirmation" {if $profile->hooplaCheckOutConfirmation==1}checked='checked'{/if} data-switch="">
							{else}
								{if $profile->hooplaCheckOutConfirmation==0}No{else}Yes{/if}
							{/if}
						</div>
					</div>
					{if !$offline && $edit == true}
						<div class="form-group">
							<div class="col-xs-6 col-xs-offset-6">
								<button type="submit" name="updateHoopla" class="btn btn-sm btn-primary">{translate text="Update Hoopla Options"}</button>
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
				You must sign in to view this information. Click <a href="/MyAccount/Login">here</a> to sign in.
			</div>
		{/if}
	</div>
{/strip}