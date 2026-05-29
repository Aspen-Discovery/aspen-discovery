{strip}
	<section class="placard" id="placard{$placard->id}">
		{if $dismissPlacardLocation == 1}
		<div class="row">
			<div class="col-xs-12 text-right">
				{if !empty($placard->dismissable) && $loggedIn}
					<div class="btn btn-sm btn-warning placard-dismiss" onclick="return AspenDiscovery.Account.dismissPlacard('{$activeUserId}', '{$placard->id}')">{if $dismissPlacardButtonAsIcon == 1}<i class="fas fa-times"></i>{else}{translate text="Don't show this again" isPublicFacing=true}{/if}</div>
				{/if}
			</div>
		</div>
		{/if}
		<script type="text/javascript">
			AspenDiscovery.WebBuilder.trackPlacardView('{$placard->id}');
		</script>
		<div class="row">
			<div class="col-xs-12">
				{* If body has no anchor tags, wrap entire placard content with link. *}
				{if $placard->link && !$placard->bodyHasAnchor()}
					{if $placard->sourceType == 'web_resource'}
						<a href="javascript:;" onclick="AspenDiscovery.WebBuilder.placardClickHandler('{$placard->id}'); AspenDiscovery.WebBuilder.getWebResource('{$placard->sourceId}', true);" class="placard-link" aria-label="{translate text=$placard->title inAttribute=true isAdminEnteredData=true isPublicFacing=true} ({translate text='opens in new window' isPublicFacing=true inAttribute=true})">
					{else}
						<a href="{$placard->link}" target="_blank" class="placard-link" aria-label="{translate text=$placard->title inAttribute=true isAdminEnteredData=true isPublicFacing=true} ({translate text='opens in new window' isPublicFacing=true inAttribute=true})" onclick="AspenDiscovery.WebBuilder.placardClickHandler('{$placard->id}');">
					{/if}
				{/if}

				{if !empty($placard->image)}
					{* If body has anchor tags, only make the image clickable. *}
					{if $placard->link && $placard->bodyHasAnchor()}
						{if $placard->sourceType == 'web_resource'}
							<a href="javascript:;" onclick="AspenDiscovery.WebBuilder.placardClickHandler('{$placard->id}'); AspenDiscovery.WebBuilder.getWebResource('{$placard->sourceId}', true);" class="placard-link" aria-label="{translate text=$placard->title inAttribute=true isAdminEnteredData=true isPublicFacing=true} ({translate text='opens in new window' isPublicFacing=true inAttribute=true})">
						{else}
							<a href="{$placard->link}" target="_blank" class="placard-image-link" aria-label="{translate text=$placard->title inAttribute=true isAdminEnteredData=true isPublicFacing=true} ({translate text='opens in new window' isPublicFacing=true inAttribute=true})" onclick="AspenDiscovery.WebBuilder.placardClickHandler('{$placard->id}');">
						{/if}
					{/if}
					<img src="/files/original/{$placard->image}" class="placard-image" alt="{if (empty($placard->altText))}{translate text=$placard->title inAttribute=true isPublicFacing=true isAdminEnteredData=true}{else}{translate text=$placard->altText inAttribute=true isPublicFacing=true isAdminEnteredData=true}{/if}">
					{if $placard->link && $placard->bodyHasAnchor()}
						</a>
					{/if}
				{/if}
				{if !empty($placard->body)}
					<span class="placard-body">
						{$placard->body}
					</span>
				{/if}

				{if !empty($placard->css)}
					<style>{$placard->css}</style>
				{/if}

				{if $placard->link && !$placard->bodyHasAnchor()}
					</a>
				{/if}
			</div>
		</div>
		{if $dismissPlacardLocation == 0}
			<div class="row">
				<div class="col-xs-12 text-right">
					{if !empty($placard->dismissable) && $loggedIn}
						<div class="btn btn-sm btn-warning placard-dismiss" onclick="return AspenDiscovery.Account.dismissPlacard('{$activeUserId}', '{$placard->id}')">{if $dismissPlacardButtonAsIcon == 1}<i class="fas fa-times"></i>{else}{translate text="Don't show this again" isPublicFacing=true}{/if}</div>
					{/if}
				</div>
			</div>
		{/if}
		{if $showDebuggingInformation && !empty($placard->debugCandidates)}
			<div class="row">
				<div class="col-xs-12">
					<small class="text-muted">
						<strong>Debug: Placard Candidates</strong><br>
						{foreach from=$placard->debugCandidates item=candidate}
							{if $candidate.isSelected}<strong>{/if}
							{$candidate.title} (trigger: "{$candidate.triggerWord}", score: {$candidate.score})
							{if $candidate.isSelected} <- Selected{/if}
							{if $candidate.isSelected}</strong>{/if}
							<br>
						{/foreach}
					</small>
				</div>
			</div>
		{/if}
	</section>
{/strip}
