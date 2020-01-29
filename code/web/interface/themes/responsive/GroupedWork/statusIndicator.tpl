{strip}
{if $statusInformation->isAvailableHere()}
	{if $statusInformation->isAvailableOnline()}
		<div class="related-manifestation-shelf-status available">{translate text='Available Online'}</div>
	{elseif $statusInformation->isAllLibraryUseOnly()}
		<div class="related-manifestation-shelf-status available">{translate text="It's Here (library use only)"}</div>
	{else}
		{if $showItsHere}
			<div class="related-manifestation-shelf-status available">{translate text="It's Here"}</div>
		{else}
			<div class="related-manifestation-shelf-status available">{translate text='On Shelf'}</div>
		{/if}
	{/if}
{elseif $statusInformation->isAvailableLocally()}
	{if $statusInformation->isAvailableOnline()}
		<div class="related-manifestation-shelf-status available">{translate text="Available Online"}</div>
	{elseif $statusInformation->isAllLibraryUseOnly()}
		<div class="related-manifestation-shelf-status available">{translate text='On Shelf (library use only)'}</div>
	{elseif $scopeType == 'Location'}
		<div class="related-manifestation-shelf-status availableOther">{translate text="Available at another branch"}</div>
	{else}
		<div class="related-manifestation-shelf-status available">{translate text='On Shelf'}</div>
	{/if}
{elseif $statusInformation->isAvailableOnline()}
	<div class="related-manifestation-shelf-status available">{translate text="Available Online"}</div>
{elseif $statusInformation->isAllLibraryUseOnly()}
	{if $isGlobalScope}
		<div class="related-manifestation-shelf-status available">{translate text='On Shelf'} (library use only)</div>
	{else}
		{if $statusInformation->isAvailable() && $statusInformation->hasLocalItem()}
			<div class="related-manifestation-shelf-status availableOther">{translate text='Checked Out/Available Elsewhere'} ({translate text="library use only"})</div>
		{elseif $statusInformation->isAvailable()}
			<div class="related-manifestation-shelf-status availableOther">{translate text='Available from another library'} ({translate text="library use only"})</div>
		{else}
			<div class="related-manifestation-shelf-status checked_out">{translate text='Checked Out'} ({translate text="library use only"})</div>
		{/if}
	{/if}
{elseif $statusInformation->isAvailable() && $statusInformation->hasLocalItem()}
	<div class="related-manifestation-shelf-status availableOther">{translate text='Checked Out/Available Elsewhere'}</div>
{elseif $statusInformation->isAvailable()}
	{if $isGlobalScope}
		<div class="related-manifestation-shelf-status available">{translate text='On Shelf'}</div>
	{else}
		<div class="related-manifestation-shelf-status availableOther">{translate text='Available from another library'}</div>
	{/if}
{else}
	<div class="related-manifestation-shelf-status checked_out">
		{if $statusInformation->getGroupedStatus()}{$statusInformation->getGroupedStatus()|translate}{else}{translate text="Withdrawn/Unavailable"}{/if}
	</div>
{/if}
{if ($statusInformation->getNumHolds() > 0 || $statusInformation->getOnOrderCopies() > 0) && ($showGroupedHoldCopiesCount || $viewingIndividualRecord == 1)}
	<div class="smallText">
		{if $statusInformation->getNumHolds() > 0}
			{$statusInformation->getCopies()} {if $statusInformation->getCopies() == 1}copy{else}copies{/if}, {$statusInformation->getNumHolds()} {if $statusInformation->getNumHolds() == 1}person is{else}people are{/if} on the wait list.
		{/if}
		{* TODO: This needs to be updated to work for manifestations as well as records *}
{*		{if is_array($statusInformation) && $statusInformation.volumeHolds}*}
{*			<br/>*}
{*			{foreach from=$statusInformation.volumeHolds item=volumeHoldInfo}*}
{*				&nbsp;&nbsp;{$volumeHoldInfo->getNumHolds()} waiting for {$volumeHoldInfo.label}<br>*}
{*			{/foreach}*}
{*		{/if}*}
		{if $statusInformation->getOnOrderCopies() > 0}
			<br/>
			{if $showOnOrderCounts}
				{$statusInformation->getOnOrderCopies()} {if $statusInformation->getOnOrderCopies() == 1}copy{else}copies{/if} on order.
			{else}
				{if $statusInformation->getCopies() > 0}
					{translate text="Additional copies on order"}
				{else}
					{translate text="Copies on order"}
				{/if}
			{/if}
		{/if}

	</div>
{/if}
{/strip}