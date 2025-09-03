{strip}
<div id="listEntry{$listEntryId}" class="resultsList listEntry" data-order="{$resultIndex}" data-list_entry_id="{$listEntryId}">
	<div class="row">
        {if !empty($listEditAllowed) && $printInterface === false}
			<div class="selectTitle col-xs-12 col-sm-1">
				<input type="checkbox" name="selected[{$listEntryId}]" class="titleSelect" id="selected{$listEntryId}">
			</div>
		{/if}
        {if (!empty($showCovers) && $printInterface === false) || ($printInterface === true && $printEntryCovers === true)}
			<div class="col-xs-3 col-sm-3 col-md-3 col-lg-2 text-center">
				{if $disableCoverArt != 1}
					<a href="{$eventUrl}" class="alignleft listResultImage">
						<img src="{$bookCoverUrl}" class="listResultImage img-thumbnail {$coverStyle}" alt="{translate text='Cover Image' inAttribute=true isPublicFacing=true}">
					</a>
				{/if}
			</div>
		{/if}

		<div class="{if empty($showCovers) && $printInterface === false}col-xs-9 col-sm-9 col-md-9 col-lg-10{elseif $listEditAllowed && $printInterface === false}col-xs-6 col-sm-6 col-md-6 col-lg-7{elseif $printInterface === true && $printEntryCovers === false}col-xs-12{elseif $printInterface === true && $printEntryCovers === true}col-xs-9 col-sm-9 col-md-9 col-lg-10{else}col-xs-6 col-sm-6 col-md-6 col-lg-8{/if}">
			{* Title Row *}

			<div class="row">
				<div class="col-xs-12">
					<span class="result-index">{$resultIndex})</span>&nbsp;
					<a href="{$eventUrl}" class="result-title notranslate">
						{if !$title|removeTrailingPunctuation} {translate text='Title not available' isPublicFacing=true}{else}{$title|removeTrailingPunctuation|highlight|truncate:180:"..."}{/if}
					</a>
				</div>
			</div>

			{* Description Section *}
            {if !empty($description) && $printInterface === false}
				<div class="row visible-xs">
					<div class="result-label col-tn-3 col-xs-3">{translate text="Description" isPublicFacing=true}</div>
					<div class="result-value col-tn-9 col-xs-9"><a id="descriptionLink{$id|escape}" href="#" onclick="$('#descriptionValue{$id|escape},#descriptionLink{$id|escape}').toggleClass('hidden-xs');return false;">{translate text="Click to view" isPublicFacing=true}</a></div>
				</div>
            {/if}

            {if (!empty($description) && $printInterface === false) || ($printInterface === true && $printEntryDescription === true)}
				<div class="row">
					{* Hide in mobile view *}
					<div class="result-value hidden-xs col-sm-8" id="descriptionValue{$id|escape}">
						{$description|highlight|truncate_html:450:"..."}
					</div>

					<div class="col-sm-4" style="display:flex; justify-content:center;">
						{if $recordDriver->inEvents()}
							{if $recordDriver->isRegistrationRequired()}
								<div class="btn-group btn-group-vertical btn-block">
									{if $recordDriver->isRegisteredForEvent()}
										<a href="{$recordDriver->getExternalUrl()}" class="btn btn-sm btn-info btn-wrap" target="_blank" style="width:100%"aria-label="{translate text="You Are Registered" isPublicFacing=true inAttribute=true} ({translate text='opens in new window' isPublicFacing=true inAttribute=true})"><i class="fas fa-external-link-alt" role="presentation"></i> {translate text="You Are Registered" isPublicFacing=true}</a>
									{else}
										<a href="{$recordDriver->getExternalUrl()}" class="btn btn-sm btn-info btn-wrap" target="_blank" style="width:100%"aria-label="{translate text="Check Registration" isPublicFacing=true inAttribute=true} ({translate text='opens in new window' isPublicFacing=true inAttribute=true})"><i class="fas fa-external-link-alt" role="presentation"></i> {translate text="Check Registration" isPublicFacing=true}</a>
									{/if}
									<a href="/MyAccount/MyEvents?page=1&eventsFilter=upcoming" class="btn btn-sm btn-action btn-wrap" style="width:100%">{translate text="Go To Your Events" isPublicFacing=true}</a>
								</div>
								<br>
							{else}
								<a href="/MyAccount/MyEvents?page=1&eventsFilter=upcoming" class="btn btn-sm btn-action btn-wrap" style="width:100%">{translate text="In Your Events" isPublicFacing=true}</a>
							{/if}
						{else}
							{if $recordDriver->isRegistrationRequired()}
								<div class="btn-group btn-group-vertical btn-block">
									<a href="{$recordDriver->getExternalUrl()}" class="btn btn-sm btn-action btn-register btn-wrap" target="_blank" style="width:100%"aria-label="{translate text="Registration Information" isPublicFacing=true inAttribute=true} ({translate text='opens in new window' isPublicFacing=true inAttribute=true})"><i class="fas fa-external-link-alt" role="presentation"></i> {translate text="Registration Information" isPublicFacing=true}</a>
									{if empty($offline) || $enableEContentWhileOffline}
										<a onclick="return AspenDiscovery.Account.saveEvent(this, 'Events', '{$recordDriver->getUniqueID()|escape}', '{$eventVendor}');" class="btn btn-sm btn-action btn-wrap addToYourEventsBtn" style="width:100%">{translate text="Add to Your Events" isPublicFacing=true}</a>
									{/if}
								</div>
								{*<a class="btn btn-sm btn-action btn-wrap" style="width:100%" onclick="return AspenDiscovery.Account.saveEventReg(this, 'Events', '{$recordDriver->getUniqueID()|escape}');">
									<i class="fas fa-external-link-alt" role="presentation"></i>
									{translate text="Add to Your Events and Register" isPublicFacing=true}
								</a>*}
							{elseif empty($offline) || $enableEContentWhileOffline}
								<a class="btn btn-sm btn-action btn-wrap addToYourEventsBtn" style="width:100%" onclick="return AspenDiscovery.Account.saveEvent(this, 'Events', '{$recordDriver->getUniqueID()|escape}', '{$eventVendor}');">{translate text="Add to Your Events" isPublicFacing=true}</a>
							{/if}
						{/if}
					</div>
				</div>
			{/if}


            {if $printInterface === false}
			<div class="resultActions row">
				{include file='Events/result-tools-horizontal.tpl' id=$id summTitle=$title recordUrl=$eventUrl showMoreInfo=1}
			</div>
			{/if}
		</div>

        {if !empty($listEditAllowed) && $printInterface === false}
			<div class="col-xs-2 col-sm-2 col-md-2 col-lg-2 text-right">
				<div class="btn-group-vertical" role="group">
					{if !empty($userSort) && ($resultIndex != '1')}<span class="btn btn-xs btn-default" onclick="return AspenDiscovery.Lists.changeWeight('{$listEntryId}', 'up');" title="{translate text="Move Up" isPublicFacing=true}">&#x25B2;</span>{/if}
					<a href="#" onclick="return AspenDiscovery.Account.getEditListForm({$listEntryId},{$listSelected})" class="btn btn-default">{translate text="Edit" isPublicFacing=true}</a>
					<a href="#" onclick="AspenDiscovery.confirm('Delete Title?', 'Are you sure you want to delete this?', 'Yes', 'No', true, 'AspenDiscovery.Lists.deleteEntryFromList({$listSelected}, {$listEntryId})', 'btn-danger');" class="btn btn-danger">{translate text='Delete' isPublicFacing=true}</a>
					{if !empty($userSort) && ($resultIndex != $listEntryCount)}<span class="btn btn-xs btn-default" onclick="return AspenDiscovery.Lists.changeWeight('{$listEntryId}', 'down');" title="{translate text="Move Down" isPublicFacing=true}">&#x25BC;</span>{/if}
				</div>
			</div>
		{/if}
	</div>
</div>
{/strip}
