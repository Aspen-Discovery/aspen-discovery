	<div class="holdsWithSelected{$sectionKey}">
		{assign var="sectionLabel" value=""}
		{if $sectionKey == "unavailable"}
			{assign var="sectionLabel" value="Pending"}
		{elseif $sectionKey == "interlibrary_loan"}
			{assign var="sectionLabel" value="ILL"}
		{else}
			{assign var="sectionLabel" value=""}
		{/if}
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
				<a href="#" onclick="AspenDiscovery.Account.confirmChangePickupLocationAll('{$userId}')" class="btn btn-sm btn-default" aria-description="{translate text="Click here to change the pickup location for all holds in the $sectionLabel section"}">{translate text="Change Pickup Loc. for All $sectionLabel" isPublicFacing=true}</a>
				{if $allowSelectingHoldsToExport}
					<a href="#" onclick="return AspenDiscovery.Account.exportOnlySelectedHolds('{$source}', $('#{$sectionKey}HoldSort_{$source} option:selected').val()" class="btn btn-sm btn-default" aria-description="{translate text="Click here to export selected holds in the $sectionLabel section to CSV"}">{translate text="Export Selected $sectionLabel to CSV" isPublicFacing=true}</a>
				{/if}
				<a href="#" onclick="return AspenDiscovery.Account.exportHolds('{$source}', $('#{$sectionKey}HoldSort_{$source} option:selected').val());" class="btn btn-sm btn-default" aria-description="{translate text="Click here to export all holds in the $sectionLabel section to CSV"}">{translate text="Export All $sectionLabel to CSV" isPublicFacing=true}</a>
			</div>
		</form>
	</div>
