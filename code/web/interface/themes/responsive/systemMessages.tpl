{foreach from=$messages item='message'}
	<div class="alert alert-info system-message" id="system-message-{$message->id}">
		<div class="system-message-text {if $message->dismissable && $loggedIn}dismissable-system-message-text{/if}">
			{$message->getFormattedMessage()}
		</div>
		{if $message->dismissable && $loggedIn}
			<button type="button" class="close" data-dismiss="alert" aria-label="Close" onclick="AspenDiscovery.Account.dismissSystemMessage({$message->id})"><i class="fas fa-times"></i></button>
		{/if}
	</div>
{/foreach}