	<div class="holdsWithSelected{$sectionKey}">
		{if !empty($allowHoldsToBeGrouped)}
			<div class="alert alert-info">
				{translate text='"Group Selected Pending ILS Holds" requires two or more ILS Holds to be selected.' isPublicFacing=true}
			</div>
		{/if}
		{assign var="sectionLabel" value=""}
		{if $sectionKey == "unavailable"}
			{assign var="sectionLabel" value="Pending"}
		{elseif $sectionKey == "cancelled"}
			{assign var="sectionLabel" value="Cancelled"}
		{elseif $sectionKey == "interlibrary_loan"}
			{assign var="sectionLabel" value="ILL"}
		{else}
			{assign var="sectionLabel" value=""}
		{/if}
		{if $sectionKey != 'cancelled'}
			<form id="withSelectedHoldsFormBottom{$sectionKey}" action="{$fullPath}">
				<div class="btn-group">
					<a href="#" onclick="AspenDiscovery.Account.confirmCancelHoldSelected()" class="btn btn-sm btn-default btn-warning" aria-description="{translate text="Click here to cancel selected holds in the $sectionLabel section"}">{translate text="Cancel Selected $sectionLabel" isPublicFacing=true}</a>
					<a href="#" onclick="AspenDiscovery.Account.confirmCancelHoldAll()" class="btn btn-sm btn-default btn-warning" aria-description="{translate text="Click here to cancel all holds in the $sectionLabel section"}">{translate text="Cancel All $sectionLabel" isPublicFacing=true}</a>
					{if !empty($allowFreezeAllHolds)}
						<a href="#" onclick="AspenDiscovery.Account.confirmFreezeHoldSelected('', '', '', {if !empty($suspendRequiresReactivationDate)}true{else}false{/if})" class="btn btn-sm btn-default" aria-description="{translate text="Click here to freeze selected holds in the $sectionLabel section"}">{translate text="Freeze Selected $sectionLabel" isPublicFacing=true}</a>
						<a href="#" onclick="AspenDiscovery.Account.confirmFreezeHoldAll('{$userId}', {if !empty($suspendRequiresReactivationDate)}true{else}false{/if})" class="btn btn-sm btn-default" aria-description="{translate text="CLick here to freeze all holds in the $sectionLabel section"}">{translate text="Freeze All $sectionLabel" isPublicFacing=true}</a>
						<a href="#" onclick="AspenDiscovery.Account.confirmThawHoldSelected()" class="btn btn-sm btn-default" aria-description="{translate text="Click here to thaw selected holds in the $sectionLabel section"}">{translate text="Thaw Selected $sectionLabel" isPublicFacing=true}</a>
						<a href="#" onclick="AspenDiscovery.Account.confirmThawHoldAll('{$userId}')" class="btn btn-sm btn-default" aria-description="{translate text="Click here to thaw all holds in the $sectionLabel section"}">{translate text="Thaw All $sectionLabel" isPublicFacing=true}</a>
					{/if}
					{if $allowHoldsToBeGrouped}
						<a href="#" onclick="AspenDiscovery.Account.groupSelectedPendingHolds('{$source}', $('#unavailableHoldSort_{$source} option:selected').val());" class="btn btn-sm btn-default groupSelectedHoldsButton disabled" aria-description="{translate text="Click here to group selected pending ILS holds"}">{translate text="Group Selected Pending ILS Holds" isPublicFacing=true}</a>
					{/if}
					{if $allowSelectingHoldsToExport}
						<a href="#" onclick="return AspenDiscovery.Account.exportOnlySelectedHolds('{$source}', $('#{$sectionKey}HoldSort_{$source} option:selected').val()" class="btn btn-sm btn-default" aria-description="{translate text="Click here to export selected holds in the $sectionLabel section to CSV"}">{translate text="Export Selected $sectionLabel to CSV" isPublicFacing=true}</a>
					{/if}
					<a href="#" onclick="return AspenDiscovery.Account.exportHolds('{$source}', $('#{$sectionKey}HoldSort_{$source} option:selected').val());" class="btn btn-sm btn-default" aria-description="{translate text="Click here to export all holds in the $sectionLabel section to CSV"}">{translate text="Export All $sectionLabel to CSV" isPublicFacing=true}</a>
					<a class="btn btn-default btn-sm" href="#" onclick="return AspenDiscovery.Account.reloadHolds();" title="{translate text="Refresh" isPublicFacing=true}">{translate text="Refresh" isPublicFacing=true inAttribute=true} <i class="fas fa-sync-alt" role="presentation"></i></a>
				</div>
			</form>
		{elseif $showCancelled}
			<form id="withSelectedHoldsFormBottom{$sectionKey}" action="{$fullPath}">
				<div class="btn-group">
					<a href="#" onclick="return AspenDiscovery.Account.exportHolds('{$source}', $('#{$sectionKey}HoldSort_{$source} option:selected').val());" class="btn btn-sm btn-default" aria-description="{translate text="Click here to export all holds in the $sectionLabel section to CSV"}">{translate text="Export All $sectionLabel to CSV" isPublicFacing=true}</a>
				</div>
			</form>
		{/if}
	</div>
