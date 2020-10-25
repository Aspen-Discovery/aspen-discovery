{if $statusInformation->isAvailableHere()}
	{if $statusInformation->isAvailableOnline()}
		<div class="related-manifestation-shelf-status label label-success">{translate text='Available Online'}</div>
	{elseif $statusInformation->isAllLibraryUseOnly()}
		<div class="related-manifestation-shelf-status label label-success">{translate text="It's Here (library use only)"}</div>
	{else}
		{if $showItsHere}
			<div class="related-manifestation-shelf-status label label-success">{translate text="It's Here"}</div>
		{else}
			<div class="related-manifestation-shelf-status label label-success">{translate text='On Shelf'}</div>
		{/if}
	{/if}
{elseif $statusInformation->isAvailableLocally()}
	{if $statusInformation->isAvailableOnline()}
		<div class="related-manifestation-shelf-status label label-success">{translate text="Available Online"}</div>
	{elseif $statusInformation->isAllLibraryUseOnly()}
		<div class="related-manifestation-shelf-status label label-success">{translate text='On Shelf (library use only)'}</div>
	{elseif $scopeType == 'Location'}
		<div class="related-manifestation-shelf-status label label-warning">{translate text="Available at another branch"}</div>
	{else}
		<div class="related-manifestation-shelf-status label label-success">{translate text='On Shelf'}</div>
	{/if}
{elseif $statusInformation->isAvailableOnline()}
	<div class="related-manifestation-shelf-status label label-success">{translate text="Available Online"}</div>
{elseif $statusInformation->isAllLibraryUseOnly()}
	{if $isGlobalScope}
		<div class="related-manifestation-shelf-status label label-success">{translate text='On Shelf'} (library use only)</div>
	{else}
		{if $statusInformation->isAvailable() && $statusInformation->hasLocalItem()}
			<div class="related-manifestation-shelf-status label label-warning">{translate text='Checked Out/Available Elsewhere'} ({translate text="library use only"})</div>
		{elseif $statusInformation->isAvailable()}
			<div class="related-manifestation-shelf-status label label-warning">{translate text='Available from another library'} ({translate text="library use only"})</div>
		{else}
			<div class="related-manifestation-shelf-status label label-danger">{translate text='Checked Out'} ({translate text="library use only"})</div>
		{/if}
	{/if}
{elseif $statusInformation->isAvailable() && $statusInformation->hasLocalItem()}
	<div class="related-manifestation-shelf-status label label-warning">{translate text='Checked Out/Available Elsewhere'}</div>
{elseif $statusInformation->isAvailable()}
	{if $isGlobalScope}
		<div class="related-manifestation-shelf-status label label-success">{translate text='On Shelf'}</div>
	{else}
		<div class="related-manifestation-shelf-status label label-warning">{translate text='Available from another library'}</div>
	{/if}
{else}
	<div class="related-manifestation-shelf-status label label-danger">
		{if $statusInformation->getGroupedStatus()}{$statusInformation->getGroupedStatus()|translate}{else}{translate text="Withdrawn/Unavailable"}{/if}
	</div>
{/if}
{if ($statusInformation->getNumHolds() > 0 || $statusInformation->getOnOrderCopies() > 0) && ($showGroupedHoldCopiesCount || $viewingIndividualRecord == 1)}
	<div class="smallText">
		{$statusInformation->getNumberOfCopiesMessage()}
	</div>
{/if}