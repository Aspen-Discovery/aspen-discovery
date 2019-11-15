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

			<h1>{translate text='OverDrive Options'}</h1>
			{if $offline}
				<div class="alert alert-warning">{translate text=offline_notice defaultText="<strong>The library system is currently offline.</strong> We are unable to retrieve information about your account at this time."}</div>
			{else}
				{* MDN 7/26/2019 Do not allow access for linked users *}
				{*				{include file="MyAccount/switch-linked-user-form.tpl" label="View Account Settings for" actionPath="/MyAccount/OverDriveOptions"}*}

				{* Empty action attribute uses the page loaded. this keeps the selected user patronId in the parameters passed back to server *}
				<form action="" method="post" class="form-horizontal">
					<input type="hidden" name="updateScope" value="overdrive">
					<div class="form-group">
						<div class="col-xs-4"><label for="overdriveEmail" class="control-label">{translate text='OverDrive Hold email'}</label></div>
						<div class="col-xs-8">
							{if $edit == true}<input name="overdriveEmail" id="overdriveEmail" class="form-control" value='{$profile->overdriveEmail|escape}' size='50' maxlength='75'>{else}{$profile->overdriveEmail|escape}{/if}
						</div>
					</div>
					<div class="form-group">
						<div class="col-xs-4"><label for="overdriveAutoCheckout" class="control-label">{translate text="overdrive_auto_checkout" defaultText="Automatically checkout when available"}</label></div>
						<div class="col-xs-8">
                            {if $edit == true}
								<input type="checkbox" name="overdriveAutoCheckout" id="overdriveAutoCheckout" {if $profile->overdriveAutoCheckout==1}checked='checked'{/if} data-switch="">
                            {else}
                                {if $profile->overdriveAutoCheckout==0}No{else}Yes{/if}
                            {/if}
						</div>
					</div>
					<div class="form-group">
						<div class="col-xs-4"><label for="promptForOverdriveEmail" class="control-label">{translate text='Prompt for OverDrive email'}</label></div>
						<div class="col-xs-8">
                            {if $edit == true}
								<input type="checkbox" name="promptForOverdriveEmail" id="promptForOverdriveEmail" {if $profile->promptForOverdriveEmail==1}checked='checked'{/if} data-switch="">
                            {else}
                                {if $profile->promptForOverdriveEmail==0}No{else}Yes{/if}
                            {/if}
						</div>
					</div>
					<h2>{translate text="Default Lending Periods"}</h2>
					{foreach from=$options.lendingPeriods item=lendingPeriod}
						<div class="form-group">
							<div class="col-xs-4"><label class="control-label">{$lendingPeriod.formatType}</label></div>
							<div class="col-xs-8">
								<div class="btn-group btn-group-toggle" data-toggle="buttons">
									{foreach from=$lendingPeriod.options key=value item=optionName}
										<label class="btn btn-default {if $optionName == $lendingPeriod.lendingPeriod}active{/if}">
											<input type="radio" value="{$optionName}" name="{$lendingPeriod.formatType}" {if $optionName == $lendingPeriod.lendingPeriod}checked{/if} >{$optionName} days
										</label>
									{/foreach}
								</div>
							</div>
						</div>
					{/foreach}
					{if !$offline && $edit == true}
						<div class="form-group">
							<div class="col-xs-8 col-xs-offset-4">
								<button type="submit" name="updateOverDrive" class="btn btn-primary">{translate text="Update Options"}</button>
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
				You must login to view this information. Click <a href="/MyAccount/Login">here</a> to login.
			</div>
		{/if}
	</div>
{/strip}
