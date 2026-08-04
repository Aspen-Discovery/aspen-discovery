{strip}
	<div id="main-content">
		{if !empty($loggedIn)}
			{if !empty($noHomeLibrary)}
				<div class="page">
					<div class="alert alert-warning">
						{translate text="Your account does not have an associated home library. Please contact your library for assistance." isPublicFacing=true}
					</div>
				</div>
			{else}

				<h1>{$readerName}</h1>
				{if !empty($profile->_web_note)}
					<div class="row">
						<div id="web_note" class="alert alert-info text-center col-xs-12">{$profile->_web_note}</div>
					</div>
				{/if}
				{if !empty($accountMessages)}
					{include file='systemMessages.tpl' messages=$accountMessages}
				{/if}
				{if !empty($offline)}
					<div class="alert alert-warning"><strong>{translate text=$offlineMessage isPublicFacing=true}</strong></div>
				{else}
					<form action="" method="post">
						<input type="hidden" name="updateScope" value="overdrive">
						<div class="form-group propertyRow">
							<label for="overdriveEmail" class="control-label">{translate text='%1% Hold email' 1=$readerName isPublicFacing=true}</label>
							{if $edit == true}<input name="overdriveEmail" id="overdriveEmail" class="form-control" value='{$profile->overdriveEmail|escape}' size='50' maxlength='75'>{else}{$profile->overdriveEmail|escape}{/if}
						</div>
						<div class="form-group propertyRow">
							<label for="promptForOverdriveEmail" class="control-label">{translate text='Prompt for %1% email' 1=$readerName isPublicFacing=true}</label>&nbsp;
							{if $edit == true}
								<input type="checkbox" name="promptForOverdriveEmail" id="promptForOverdriveEmail" {if $profile->promptForOverdriveEmail==1}checked='checked'{/if} data-switch="">
							{else}
								{if $profile->promptForOverdriveEmail==0}{translate text="No" isPublicFacing=true}{else}{translate text="Yes" isPublicFacing=true}{/if}
							{/if}
						</div>
						<h2>{translate text="Default Lending Periods" isPublicFacing=true}</h2>
						{foreach from=$availableSettings item=setting}
							{assign var=settingId value=$setting->id}
							{assign var="options" value=$optionsBySetting.$settingId}
							{if !empty($options.lendingPeriods)}
								<h3>{translate text=$setting->name isPublicFacing=true}</h3>
								{foreach from=$options.lendingPeriods item=lendingPeriod}
									<div class="form-group propertyRow">
										<label class="control-label" id="{$lendingPeriod.formatType}_{$settingId}_Label">{translate text=$lendingPeriod.formatType isPublicFacing=true}&nbsp;
											<select class="form-control" aria-labelledby="{$lendingPeriod.formatType}_{$settingId}_Label" name="{$lendingPeriod.formatType}_{$settingId}">
											{foreach from=$lendingPeriod.options key=value item=optionName}
												<option value="{$optionName}" {if $optionName == $lendingPeriod.lendingPeriod}selected{/if}>{translate text="%1% days" 1=$optionName isPublicFacing=true}</option>
											{/foreach}
											</select>
										</label>
									</div>
								{/foreach}
							{/if}
						{/foreach}
						{if empty($offline) && $edit == true}
							<div class="form-group propertyRow">
								<button type="submit" name="updateOverDrive" class="btn btn-sm btn-primary">{translate text="Update Options" isPublicFacing=true}</button>
							</div>
						{/if}
					</form>
					{if $qrAuthEnabled}
						<h2>{translate text="%1% Single Sign-On" 1=$readerName isPublicFacing=true}</h2>
						<p>{translate text="Link your Aspen account to %1% once and future checkouts and holds can skip the sign-in screen. The button opens a window provided by OverDrive." 1=$readerName isPublicFacing=true}</p>
						{foreach from=$qrAuthStatuses key=settingId item=status}
							{assign var=setting value=$availableSettings.$settingId}
							{if $setting}
								<div class="panel panel-default">
									<div class="panel-body">
										{if $status.connected}
											<p class="{if $status.expired}text-warning{else}text-success{/if}">
												{if $status.expired}
													{translate text="A saved session exists but needs to be refreshed. Aspen will refresh it automatically next time you access %1%." 1=$readerName isPublicFacing=true}
												{else}
													{translate text="Linked to your %1% account." 1=$readerName isPublicFacing=true}
												{/if}
											</p>
											{if !empty($status.updated)}
												<p class="help-block">
													{translate text="Last updated %1%" 1=$status.updated|date_format:"%b %e, %l:%M %p" isPublicFacing=true}
												</p>
											{/if}
											<a href="/OverDrive/QRCodeAuth?disconnect=1&settingId={$settingId}" class="btn btn-danger">
												{translate text="Disconnect" isPublicFacing=true}
											</a>
										{else}
											<p class="text-muted">
												{translate text="Not yet connected." isPublicFacing=true}
											</p>
											<a href="/OverDrive/QRCodeAuth?settingId={$settingId}" class="btn btn-primary">
												{translate text="Connect with QR Code" isPublicFacing=true}
											</a>
										{/if}
									</div>
								</div>
							{/if}
						{/foreach}
					{/if}

					<script type="text/javascript">
						{* Initiate any checkbox with a data attribute set to data-switch=""  as a bootstrap switch *}
						{literal}
						$(function(){ $('input[type="checkbox"][data-switch]').bootstrapSwitch()});
						{/literal}
					</script>
				{/if}
			{/if}
		{else}
			<div class="page">
				{translate text="You must sign in to view this information." isPublicFacing=true}<a href='/MyAccount/Login' class="btn btn-primary">{translate text="Sign In" isPublicFacing=true}</a>
			</div>
		{/if}
	</div>
{/strip}
