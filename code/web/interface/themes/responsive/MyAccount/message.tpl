{strip}
			{if $userMessage->title}
				<h2 id="messageTitle">{$userMessage->title}</h2>
				{else}
				<h2 id="messageTitle">{translate text="Unknown Title" isPublicFacing=true}</h2>
			{/if}
			<div class="text-left">
				<p class="text-muted"><small>{translate text="Sent on %1%" 1=$userMessage->dateSent|date_format:"%B %e, %Y %l:%M %p" isPublicFacing=true}</small></p>
			</div>
			<div class="btn-toolbar">
				<div class="btn-group btn-group-sm">
					{if !$userMessage->isRead}
						<a class="btn btn-default" href="" onclick="return AspenDiscovery.Account.markILSMessageAsRead({$userMessage->id})"><i class="far fa-envelope-open" role="presentation"></i> {translate text="Mark As Read" isPublicFacing=true}</a>
					{else}
						<a class="btn btn-default" href="" onclick="return AspenDiscovery.Account.markILSMessageAsUnread({$userMessage->id})"><i class="far fa-envelope" role="presentation"></i> {translate text="Mark As Unread" isPublicFacing=true}</a>
					{/if}
				</div>
			</div>
	{if $userMessage->content}
		<p id="messageContent" style="margin-top:1em">{$userMessage->content}</p>
		{else}
		<p id="messageContent" style="margin-top:1em">{translate text="Unknown content" isPublicFacing=true}</p>
	{/if}
{/strip}