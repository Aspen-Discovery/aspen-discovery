{strip}
<div class="result row hyperhold-group" id="hyperhold_{$record.visual_hold_id}">
	<div class="col-xs-12">
		<input type="checkbox" class="select-hyperhold-group" title="{translate text='Select all holds in this group' isPublicFacing=true}" />
		<span class="result-index">{$resultIndex}</span>&nbsp;
		<strong>{translate text="Hyperhold Group" isPublicFacing=true}:</strong> {$record.visual_hold_id}
		&nbsp;|&nbsp;
		<strong>{translate text="Number of Holds in Group" isPublicFacing=true}:</strong> {$record.holdCount}
		&nbsp;|&nbsp;
		<strong>{translate text="On Hold For" isPublicFacing=true}:</strong> {$record.userName}
		<a href="#" onclick="$('#hyperhold_details_{$record.visual_hold_id}').toggle(); return false;" class="btn btn-sm btn-default" style="margin-left: 15px;">
			<i class="fas fa-list"></i> {translate text="View All Records" isPublicFacing=true} ({$record.holdCount})
		</a>
	</div>
</div>

<div id="hyperhold_details_{$record.visual_hold_id}" class="holdDetails" style="display:none; margin-top:15px;">
	{foreach from=$record.holds item=hold name="holdLoop"}
		{include file="MyAccount/ilsHold.tpl" record=$hold section='pending' resultIndex=$smarty.foreach.holdLoop.iteration showCovers=$showCovers}
	{/foreach}
</div>
<div>
	<form id="controlGroupedHoldsForm">
		<div class="btn-group">
			<a href="#" onclick="AspenDiscovery.Account.requestGroupConfirmation()" class="btn btn-sm btn-default" aria-description="{translate text="Click here to group selected holds"}">{translate text="Regroup Selected Holds" isPublicFacing=true}</a>
			<a href="#" onclick="AspenDiscovery.Account.deleteHoldsGroup({$record.hold_group_id}, {$record.visual_hold_id}, {$record.holds[0]->userId})" class="btn btn-sm btn-warning" aria-description="{Translate text='Click to here to delete hold group'} {$record.visual_hold_id}">{translate text="Ungroup Hold Group " isPublicFacing=true}{$record.visual_hold_id}</a>
		</div>
	</form>
</div>
{/strip}

<script>
	$(document).on('change', '.select-hyperhold-group', function() {
		var groupId = $(this).closest('.hyperhold-group').attr('id');
		if (!groupId) return;

		var idNum = groupId.split('_')[1];

		$('#hyperhold_details_' + idNum + ' input[type="checkbox"]').prop('checked', $(this).is(':checked'));
	});
</script>