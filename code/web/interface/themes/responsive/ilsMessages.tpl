{foreach from=$messages item='message'}
	<div class="{if !empty($message.messageStyle)}alert alert-{$message.messageStyle}{/if} ils-message">
		<div class="system-message-text">
			{$message.message}
		</div>
	</div>
{/foreach}