{strip}
	<div class="placard" id="placard{$placard->id}">
		{if $placard->link}
			<a href="{$placard->link}" target="_blank" class="placard-link">
		{/if}
		<div class="row">
			<div class="col-xs-12">

				{if !empty($placard->image)}
					<img src="/files/original/{$placard->image}" class="placard-image" alt="{if (empty($placard->altText))}{translate text=$placard->title inAttribute=true isPublicFacing=true isAdminEnteredData=true}{else}{translate text=$placard->altText inAttribute=true isPublicFacing=true isAdminEnteredData=true}{/if}">
				{/if}
				{if !empty($placard->body)}
					<span class="placard-body">
						{$placard->body}
					</span>
				{/if}

				{if !empty($placard->css)}
					<style type="text/css">{$placard->css}</style>
				{/if}
			</div>
		</div>
		{if $placard->link}
			</a>
		{/if}
		<div class="row">
			<div class="col-xs-12 text-right">
				{if !empty($placard->dismissable) && $loggedIn}
					<div class="btn btn-sm btn-warning placard-dismiss" onclick="return AspenDiscovery.Account.dismissPlacard('{$activeUserId}', '{$placard->id}')">{translate text="Don't show this again" isPublicFacing=true}</div>
				{/if}
			</div>
		</div>
	</div>
{/strip}