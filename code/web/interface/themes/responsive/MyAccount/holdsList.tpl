{assign var="hideCoversFormDisplayed" value=false}
{foreach from=$recordList item=sectionData key=sectionKey}
	{if $sectionKey == 'available' && !$showAvailableHoldsSection}
		{continue}
	{/if}
	<h2>{if $sectionKey == 'available'}{translate text="Holds Ready For Pickup" isPublicFacing=true}
		{elseif $source=='interlibrary_loan'}{translate text="Pending Requests" isPublicFacing=true}
		{elseif ($source=='ils' || $source=='all') && $sectionKey == 'cancelled' && $showCancelled}{translate text="Cancelled Holds" isPublicFacing=true}
		{elseif $sectionKey != 'cancelled' && $sectionKey != 'available'}{translate text="Pending Holds" isPublicFacing=true}
		{/if}
	</h2>
	{if !empty($showHoldHelpMessages) && count($recordList.$sectionKey) > 0}
		<div class="holdHelpMessage alert alert-info">
			<i class="fas fa-info-circle"></i>
			{if $sectionKey == 'available'}
				{translate text="These titles have arrived at the library or are available online for you to use." isPublicFacing=true}
				{*These titles have arrived at the library or are available online for you to use.*}
			{elseif $sectionKey == 'cancelled' && $showCancelled}
				{translate text="These are your cancelled requests." isPublicFacing=true}
			{elseif $source == 'interlibrary_loan'}
				{translate text="These requests will be filled by another library and sent to your library. We will notify you when a title is available." isPublicFacing=true}
			{elseif $sectionKey != 'cancelled' && $sectionKey != 'available'}
				{if not $notification_method or $notification_method eq 'Unknown'}
					{translate text="These titles are currently checked out to other patrons. We will notify you when a title is available." isPublicFacing=true}
				{else}
					{translate text="These titles are currently checked out to other patrons. We will notify you via %1% when a title is available." 1=$notification_method isPublicFacing=true}

					{/if}
			{/if}
			<button type="button" class="close" data-dismiss="holdHelpMessage" aria-label="{translate text="Close" isPublicFacing=true inAttribute=true}" onclick="AspenDiscovery.Account.dismissHoldHelpMessages()"><i class="fas fa-times" role="presentation"></i></button>
		</div>
	{/if}
	{if is_array($recordList.$sectionKey) && count($recordList.$sectionKey) > 0}
		{if $source == 'ils' && $sectionKey == 'available' && $showCurbsidePickups}
			<div id="curbsidePickupButton" style="margin-bottom: 1em">
				<a href="/MyAccount/CurbsidePickups" class="btn btn-primary">{translate text="Schedule a Curbside Pickup" isPublicFacing=true}</a>
			</div>
		{/if}

		{if $sectionKey != 'cancelled'}
			<div id="pager" class="navbar form-inline">
				<label for="{$sectionKey}HoldSort_{$source}" class="control-label">{translate text='Sort by' isPublicFacing=true}&nbsp;</label>
				<select name="{$sectionKey}HoldSort_{$source}" id="{$sectionKey}HoldSort_{$source}" class="form-control" onchange="AspenDiscovery.Account.loadHolds('{$source}', $('#availableHoldSort_{$source} option:selected').val(), $('#unavailableHoldSort_{$source} option:selected').val());">
					{foreach from=$sortOptions[$sectionKey] item=sortDesc key=sortVal}
						<option value="{$sortVal}"{if $defaultSortOption[$sectionKey] == $sortVal} selected="selected"{/if}>{translate text=$sortDesc isPublicFacing=true}</option>
					{/foreach}
				</select>

				{if empty($hideCoversFormDisplayed)}
					{* Display the Hide Covers switch above the first section that has holds; and only display it once *}
					<label for="hideCovers_{$source}" class="control-label checkbox pull-right"> {translate text="Hide Covers" isPublicFacing=true} <input id="hideCovers_{$source}" type="checkbox" onclick="AspenDiscovery.Account.loadHolds('{$source}', $('#availableHoldSort_{$source} option:selected').val(), $('#unavailableHoldSort option:selected').val(), !$('#hideCovers_{$source}').is(':checked'));" {if $showCovers == false}checked="checked"{/if}></label>
					{assign var="hideCoversFormDisplayed" value=true}
				{/if}
			</div>
		{/if}

		{if empty($offline) && $sectionKey != 'available'  && count($recordList.$sectionKey) > 0}
			{include file="./holdsListActions.tpl" sectionKey=$sectionKey source=$source showCovers=$showCovers}
			<br>
		{/if}
		<div class="striped">
			{foreach from=$recordList.$sectionKey item=record name="recordLoop"}
				{if $record->type == 'ils'}
					{include file="MyAccount/ilsHold.tpl" record=$record section=$sectionKey resultIndex=$smarty.foreach.recordLoop.iteration}
				{elseif $record->type == 'overdrive'}
					{include file="MyAccount/overdriveHold.tpl" record=$record section=$sectionKey resultIndex=$smarty.foreach.recordLoop.iteration}
				{elseif $record->type == 'cloud_library'}
					{include file="MyAccount/cloudLibraryHold.tpl" record=$record section=$sectionKey resultIndex=$smarty.foreach.recordLoop.iteration}
				{elseif $record->type == 'axis360'}
					{include file="MyAccount/axis360Hold.tpl" record=$record section=$sectionKey resultIndex=$smarty.foreach.recordLoop.iteration}
				{elseif $record->type == 'hoopla'}
					{include file="MyAccount/hooplaHold.tpl" record=$record section=$sectionKey resultIndex=$smarty.foreach.recordLoop.iteration}
				{elseif $record->type == 'palace_project'}
					{include file="MyAccount/palaceProjectHold.tpl" record=$record section=$sectionKey resultIndex=$smarty.foreach.recordLoop.iteration}
				{elseif $record->type == 'interlibrary_loan' && $record->source == 'vdx'}
					{include file="MyAccount/vdxRequest.tpl" record=$record section=$sectionKey resultIndex=$smarty.foreach.recordLoop.iteration}
				{else}
					<div class="row">
						Unknown record type {$record->type}
					</div>
				{/if}
			{/foreach}
		</div>
	{else} {* Check to see if records are available *}
		{if $sectionKey == 'available'}
			{translate text='You do not have any holds that are ready to be picked up.' isPublicFacing=true}
		{elseif $sectionKey != 'available' && $sectionKey != 'cancelled'}
			{if $source == 'interlibrary_loan'}
				{translate text='You do not have any pending requests.' isPublicFacing=true}
			{else}
				{translate text='You do not have any pending holds.' isPublicFacing=true}
			{/if}
		{/if}
	{/if}
	{if empty($offline) && $sectionKey != 'available'  && count($recordList.$sectionKey) > 0}
		<br>
		{include file="./holdsListActions.tpl" sectionKey=$sectionKey source=$source showCovers=$showCovers}
	{/if}
{/foreach}
{* HyperHolds Section *}
{if $hasHyperHolds && !empty($hyperHolds) && $source == 'ils'}
	<h2>{translate text="Hyperholds" isPublicFacing=true}</h2>
	<p class="alert alert-info">
		{translate text="Multiple records are on hold for you. The library will fulfill whichever record becomes available first. Once a record is available, it will appear in the 'Ready for Pickup' section." isPublicFacing=true}
	</p>
	
	<div class="striped">
		{foreach from=$hyperHolds item=hyperHold name="hyperHoldLoop"}
			{include file="MyAccount/hyperHold.tpl" record=$hyperHold resultIndex=$smarty.foreach.hyperHoldLoop.iteration}
		{/foreach}
	</div>
	<hr>
{/if}
<a href="#" onclick="return AspenDiscovery.Account.exportHolds('{$source}', $('#availableHoldSort_{$source} option:selected').val(), $('#interlibrary_loanHoldSort_{$source} option:selected').val(), $('#unavailableHoldSort_{$source} option:selected').val());" class="btn btn-sm btn-default" aria-description="{translate text="Click here to export all holds from all sections to CSV"}">{translate text="Export All Holds to CSV" isPublicFacing=true}</a>
