{strip}
{if !isset($applyColors)}{assign var=applyColors value=true}{/if}
{if $statusInformation->isEContent()}
	{if $statusInformation->isShowStatus()}
		{* eContent, easy to handle *}
		{if $statusInformation->isAvailableOnline()}
			<div class="related-manifestation-shelf-status status-available-online {if $applyColors}label label-success label-wrap{/if}">{translate text='Available Online' isPublicFacing=true}</div>
		{else}
			{if $statusInformation->getGroupedStatus() == 'On Order'}
				<div class="related-manifestation-shelf-status status-checked-out {if $applyColors}label label-danger label-wrap{/if}">{translate text='On Order' isPublicFacing=true}</div>
			{else}
				<div class="related-manifestation-shelf-status status-checked-out {if $applyColors}label label-danger label-wrap{/if}">{translate text='Checked Out' isPublicFacing=true}</div>
			{/if}
		{/if}
	{/if}
{else}
	{* Physical materials, these get trickier *}
	{if $statusInformation->isAvailableHere()}
		{* We are at a local branch, viewing a physical copy *}
		{if $statusInformation->isAllLibraryUseOnly()}
			<div class="related-manifestation-shelf-status status-its-here-library-use-only {if $applyColors}label label-success label-wrap{/if}">{translate text="It's Here (library use only)" isPublicFacing=true}</div>
		{else}
			{if !empty($showItsHere)}
				<div class="related-manifestation-shelf-status status-its-here {if $applyColors}label label-success label-wrap{/if}">{translate text="It's Here" isPublicFacing=true}</div>
			{else}
				<div class="related-manifestation-shelf-status status-on-shelf {if $applyColors}label label-success label-wrap{/if}">{translate text='On Shelf' isPublicFacing=true}</div>
			{/if}
		{/if}
	{elseif $statusInformation->isAvailableLocally()}
		{if $statusInformation->isAllLibraryUseOnly() || $statusInformation->getGroupedStatus() == 'Library Use Only'}
			<div class="related-manifestation-shelf-status status-library-use-only {if $applyColors}label label-success label-wrap{/if}">{translate text='Library Use Only' isPublicFacing=true}</div>
		{else}
			<div class="related-manifestation-shelf-status status-on-shelf {if $applyColors}label label-success label-wrap{/if}">{translate text='On Shelf' isPublicFacing=true}</div>
		{/if}
	{elseif $statusInformation->isAllLibraryUseOnly()}
		{if !empty($isGlobalScope)}
			<div class="related-manifestation-shelf-status status-on-shelf {if $applyColors}label label-success label-wrap{/if}">{translate text='On Shelf' isPublicFacing=true} ({translate text="library use only" isPublicFacing=true})</div>
		{else}
			{if !$statusInformation->isAvailable() && $statusInformation->hasLocalItem()}
				<div class="related-manifestation-shelf-status status-checked-out-available-elsewhere {if $applyColors}label label-warning label-wrap{/if}">{translate text='Checked Out / Available Elsewhere' isPublicFacing=true} ({translate text="library use only" isPublicFacing=true})</div>
			{elseif $statusInformation->isAvailable()}
				{if $statusInformation->hasLocalItem()}
					<div class="related-manifestation-shelf-status status-library-use-only {if $applyColors}label label-success label-wrap{/if}">{translate text="Library Use Only" isPublicFacing=true}</div>
				{else}
					<div class="related-manifestation-shelf-status status-available-elsewhere {if $applyColors}label label-warning label-wrap{/if}">{translate text='Available from another library' isPublicFacing=true} ({translate text="library use only" isPublicFacing=true})</div>
				{/if}
			{else}
				<div class="related-manifestation-shelf-status status-checked-out {if $applyColors}label label-danger label-wrap{/if}">{translate text='Checked Out' isPublicFacing=true} ({translate text="library use only" isPublicFacing=true})</div>
			{/if}
		{/if}
	{elseif $statusInformation->isAvailable() && !$statusInformation->isAvailableLocally() && $statusInformation->hasLocalItem()}
		<div class="related-manifestation-shelf-status status-checked-out-available-elsewhere {if $applyColors}label label-warning label-wrap{/if}">{translate text='Checked Out/Available Elsewhere' isPublicFacing=true}</div>
	{elseif $statusInformation->isAvailable()}
		{if !empty($isGlobalScope)}
			<div class="related-manifestation-shelf-status status-on-shelf {if $applyColors}label label-success label-wrap{/if}">{translate text='On Shelf' isPublicFacing=true}</div>
		{else}
			{if $statusInformation->hasLocalItem()}
				<div class="related-manifestation-shelf-status status-on-shelf {if $applyColors}label label-success label-wrap{/if}">{translate text='On Shelf' isPublicFacing=true}</div>
			{else}
				<div class="related-manifestation-shelf-status status-available-elsewhere {if $applyColors}label label-warning label-wrap{/if}">{translate text='Available from another library' isPublicFacing=true}</div>
			{/if}
		{/if}
	{else}
		<div class="related-manifestation-shelf-status status-withdrawn {if $applyColors}label label-danger label-wrap{/if}">
			{if $statusInformation->getGroupedStatus()}{translate text=$statusInformation->getGroupedStatus() isPublicFacing=true}{else}{translate text="Withdrawn / Unavailable" isPublicFacing=true}{/if}
		</div>
	{/if}
{/if}
{if ((($statusInformation->getHoldableCopies() > 0 && $statusInformation->getNumHolds() > 0) || $statusInformation->getOnOrderCopies() > 0) && ($showGroupedHoldCopiesCount || $viewingIndividualRecord == 1) || $showGroupedHoldCopiesCount == 3)}
	<div class="related-manifestation-copies-message {if $statusInformation->getNumberOfCopiesMessage()|strstr:'wait list'} has-waitlist{/if}">{$statusInformation->getNumberOfCopiesMessage()}</div>
{/if}
{/strip}
