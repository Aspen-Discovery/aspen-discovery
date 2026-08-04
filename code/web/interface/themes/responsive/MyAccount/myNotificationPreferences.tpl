{strip}
	<div id="main-content">
		{if !empty($loggedIn)}
			<h1>{translate text='Notification Preferences' isPublicFacing=true}</h1>
			<div>
				{translate 
					text='These settings are device specific. Please be careful about turning on notifications for public devices if you do not wish other people to be able to see notifications intended for you.'
					isPublicFacing=true
				}
			</div>
			<br>
			<div>
				{foreach from=$tokens item=item}
					<token
						data-token="{$item.token}"
						data-device="{$item.deviceModel}"
						data-notify-custom="{$item.notifyCustom}" 
						data-notify-account="{$item.notifyAccount}"
						data-notify-saved-search="{$item.notifySavedSearch}"
					/>
				{/foreach}
			</div>
			{if !empty($offline)}
				<div class="alert alert-warning"><strong>{translate text=$offlineMessage isPublicFacing=true}</strong></div>
			{else}
				<div class="grant-notification-permissions">
					<label for="allowNotifications" class="control-label">{translate text='Allow Notifications' isPublicFacing=true}</label>&nbsp;
					<input type="checkbox" class="form-control" name="allowNotifications" id="allowNotifications" {if $edit == false} disabled {/if} data-switch="">
				 </div>
				 <br>
				<div class="notification-permission-controls">
					<label for="notifySavedSearch" class="control-label">{translate text='Allow Notifications for Saved Search' isPublicFacing=true}</label>&nbsp;
					<input type="checkbox" class="form-control" name="notifySavedSearch" id="notifySavedSearch" {if $edit == false} disabled {/if} data-switch="">
					<br><br>
					<label for="notifyCustom" class="control-label">{translate text='Allow Custom Notifications' isPublicFacing=true}</label>&nbsp;
					<input type="checkbox" class="form-control" name="notifyCustom" id="notifyCustom" {if $edit == false} disabled {/if} data-switch="">
					<br><br>
					<label for="notifyAccount" class="control-label">{translate text='Allow Account Notifications' isPublicFacing=true}</label>&nbsp;
					<input type="checkbox" class="form-control" name="notifyAccount" id="notifyAccount" {if $edit == false} disabled {/if} data-switch="">
				</div>
				<script type="module" type="text/javascript" src="/interface/themes/responsive/js/aspen/initFCM.js">
				</script>
			{/if}
		{else}
			<div class="page">
				{translate text="You must sign in to view this information." isPublicFacing=true}<a href='/MyAccount/Login' class="btn btn-primary">{translate text="Sign In" isPublicFacing=true}</a>
			</div>
		{/if}
	</div>
{/strip}
