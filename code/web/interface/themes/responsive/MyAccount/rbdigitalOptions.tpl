{strip}
	<div id="main-content">
		{if $loggedIn}
		{if !empty($profile->_web_note)}
			<div class="row">
				<div id="web_note" class="alert alert-info text-center col-xs-12">{$profile->_web_note}</div>
			</div>
		{/if}

		{* Alternate Mobile MyAccount Menu *}
		{include file="MyAccount/mobilePageHeader.tpl"}

		<span class='availableHoldsNoticePlaceHolder'></span>

		<h1>{translate text='RBdigital Options'}</h1>
		{if $offline}
			<div class="alert alert-warning">{translate text=offline_notice defaultText="<strong>The library system is currently offline.</strong> We are unable to retrieve information about your account at this time."}</div>
		{else}
            {* MDN 7/26/2019 Do not allow access for linked users *}
{*			{include file="MyAccount/switch-linked-user-form.tpl" label="View Account Settings for" actionPath="/MyAccount/RBdigitalOptions"}*}

			{* Empty action attribute uses the page loaded. this keeps the selected user patronId in the parameters passed back to server *}
			<form action="" method="post" class="form-horizontal">
				<input type="hidden" name="updateScope" value="rbdigital">
				<div class="form-group">
					<div class="col-xs-4"><label for="rbdigitalUsername" class="control-label">{translate text='RBdigital Username'}</label></div>
					<div class="col-xs-8">
						{if $edit == true}<input name="rbdigitalUsername" id="rbdigitalUsername" class="form-control" value='{$profile->rbdigitalUsername|escape}' size='50' maxlength='75' type="text">{else}{$profile->rbdigitalUsername|escape}{/if}
					</div>
				</div>
				<div class="form-group">
					<div class="col-xs-4"><label for="rbdigitalPassword" class="control-label">{translate text='RBdigital Password'}</label></div>
					<div class="col-xs-8">
						{if $edit == true}<input name="rbdigitalPassword" id="rbdigitalPassword" class="form-control" value='{$profile->rbdigitalPassword|escape}' size='50' maxlength='75' type="password">{else}{$profile->rbdigitalPassword|escape}{/if}
					</div>
				</div>
				{if !$offline && $edit == true}
					<div class="form-group">
						<div class="col-xs-8 col-xs-offset-4">
							<button type="submit" name="updateRbdigital" class="btn btn-sm btn-primary">{translate text="Update Options"}</button>
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
				You must login to view this information. Click <a href="{$path}/MyAccount/Login">here</a> to login.
			</div>
		{/if}
	</div>
{/strip}
