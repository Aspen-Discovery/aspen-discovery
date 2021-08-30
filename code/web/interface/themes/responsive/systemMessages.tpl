{foreach from=$messages item='message'}
	<div class="{if !empty($message->messageStyle)}alert alert-{$message->messageStyle}{/if} system-message" id="system-message-{$message->id}" role="alert" aria-live="polite">
		<div class="system-message-text {if $message->dismissable && $loggedIn}dismissable-system-message-text{/if}">
			{$message->getFormattedMessage()}
		</div>
		{if $message->dismissable && $loggedIn}
			<button type="button" class="close" data-dismiss="alert" aria-label="{translate text="Close" isPublicFacing=true}" onclick="AspenDiscovery.Account.dismissSystemMessage({$message->id})"><i class="fas fa-times"></i></button>
		{/if}
	</div>
{/foreach}