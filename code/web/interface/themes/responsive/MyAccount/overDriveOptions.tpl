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

			<h1>{translate text='OverDrive Options' isPublicFacing=true}</h1>
			{if $offline}
				<div class="alert alert-warning"><strong>{translate text="The library system is currently offline." isPublicFacing=true}</strong> {translate text="We are unable to retrieve information about your account at this time." isPublicFacing=true}</div>
			{else}
				{* MDN 7/26/2019 Do not allow access for linked users *}
				{*				{include file="MyAccount/switch-linked-user-form.tpl" label="View Account Settings for" actionPath="/MyAccount/OverDriveOptions"}*}

				{* Empty action attribute uses the page loaded. this keeps the selected user patronId in the parameters passed back to server *}
				<form action="" method="post" class="form-horizontal">
					<input type="hidden" name="updateScope" value="overdrive">
					<div class="form-group">
						<div class="col-xs-4"><label for="overdriveEmail" class="control-label">{translate text='OverDrive Hold email' isPublicFacing=true}</label></div>
						<div class="col-xs-8">
							{if $edit == true}<input name="overdriveEmail" id="overdriveEmail" class="form-control" value='{$profile->overdriveEmail|escape}' size='50' maxlength='75'>{else}{$profile->overdriveEmail|escape}{/if}
						</div>
					</div>
					<div class="form-group">
						<div class="col-xs-4"><label for="promptForOverdriveEmail" class="control-label">{translate text='Prompt for OverDrive email' isPublicFacing=true}</label></div>
						<div class="col-xs-8">
                            {if $edit == true}
								<input type="checkbox" name="promptForOverdriveEmail" id="promptForOverdriveEmail" {if $profile->promptForOverdriveEmail==1}checked='checked'{/if} data-switch="">
                            {else}
                                {if $profile->promptForOverdriveEmail==0}{translate text="No" isPublicFacing=true}{else}{translate text="Yes" isPublicFacing=true}{/if}
                            {/if}
						</div>
					</div>
					<h2>{translate text="Default Lending Periods" isPublicFacing=true}</h2>
					{foreach from=$options.lendingPeriods item=lendingPeriod}
						<div class="form-group">
							<div class="col-xs-4"><label class="control-label">{translate text=$lendingPeriod.formatType isPublicFacing=true}</label></div>
							<div class="col-xs-8">
								<div class="btn-group btn-group-toggle" data-toggle="buttons">
									{foreach from=$lendingPeriod.options key=value item=optionName}
										<label class="btn btn-default {if $optionName == $lendingPeriod.lendingPeriod}active{/if}">
											<input type="radio" value="{$optionName}" name="{$lendingPeriod.formatType}" {if $optionName == $lendingPeriod.lendingPeriod}checked{/if} >{translate text="%1% days" 1=$optionName isPublicFacing=true}
										</label>
									{/foreach}
								</div>
							</div>
						</div>
					{/foreach}
					{if !$offline && $edit == true}
						<div class="form-group">
							<div class="col-xs-8 col-xs-offset-4">
								<button type="submit" name="updateOverDrive" class="btn btn-primary">{translate text="Update Options" isPublicFacing=true}</button>
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
