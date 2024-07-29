{strip}
	<h1>{translate text="Notification History" isPublicFacing=true}</h1>
    {if empty($messages)}
		<div class="alert alert-info">
	        {translate text="You do not have any notifications at this time." isPublicFacing=true}
		</div>
    {else}
	    <div class="row">
	    <div class="col-xs-12">
		    <table class="table table-striped table-responsive table-hover" id="myMessagesTable">
			    <thead>
			    <tr>
				    <th>{translate text="Date Sent" isPublicFacing=true}</th>
				    <th>{translate text="Subject" isPublicFacing=true}</th>
				    <th>{translate text="Actions" isPublicFacing=true}</th>
			    </tr>
			    </thead>
			    <tbody>
        {foreach from=$userMessages item="message"}
		    <tr>
			    <td>
                    {if !$message->isRead}
                        <strong>{$message->dateQueued|date_format:"%D %l:%M %p"}</strong>
				    {else}
                        {$message->dateQueued|date_format:"%D %l:%M %p"}
				    {/if}
			    </td>
	            <td>
		            {if !$message->isRead}
		                <strong>{$message->title}</strong>
		            {else}
		            {$message->title}
		            {/if}
	            </td>
			    <td>
				    <div class="btn-toolbar" role="toolbar">
				    <div class="btn-group">
					    <a href="" class="btn btn-sm btn-default" onclick="return AspenDiscovery.Account.showILSMessage({$message->id})">{translate text="Open" isPublicFacing=true}</a>
                        {if !$message->isRead}
					        <a class="btn btn-sm btn-default" href="" onclick="return AspenDiscovery.Account.markILSMessageAsRead({$message->id})"><i class="far fa-envelope-open" role="presentation"></i> {translate text="Mark As Read" isPublicFacing=true}</a>
					    {else}
					        <a class="btn btn-sm btn-default" href="" onclick="return AspenDiscovery.Account.markILSMessageAsUnread({$message->id})"><i class="far fa-envelope" role="presentation"></i> {translate text="Mark As Unread" isPublicFacing=true}</a>
					    {/if}
				    </div>
				    </div>
			    </td>

		    </tr>
	    {/foreach}
			    </tbody>
		    </table>
		    {if !empty($pageLinks.all)}<div class="col-xs-12"><div class="pagination">{$pageLinks.all}</div></div>{/if}
	    </div>
	    </div>
	{/if}
{/strip}