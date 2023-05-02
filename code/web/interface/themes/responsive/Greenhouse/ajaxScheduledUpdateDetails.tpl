{strip}
	<div class="row" style="padding-bottom:1em">
		<div class="col-sm-2">
			<strong>{translate text='Status' isAdminFacing=true}</strong>
			<div class="row"><div class="col-sm-12"><span class="label {if $updateStatus == 'pending'}label-warning{elseif $updateStatus == 'failed'}label-danger{elseif $updateStatus == 'complete'}label-success{else}label-default{/if}">{$updateStatus}</span></div></div>
		</div>
		<div class="col-sm-2">
            <strong>{translate text='Updated to' isAdminFacing=true}</strong>
            <div class="row"><div class="col-sm-12">{$updateTo}</div></div>
        </div>
        <div class="col-sm-2">
            <strong>{translate text='Type' isAdminFacing=true}</strong>
            <div class="row"><div class="col-sm-12">{$updateType}</div></div>
        </div>
        <div class="col-sm-3">
            <strong>{translate text='Date Scheduled' isAdminFacing=true}</strong>
            <div class="row"><div class="col-sm-12">{$updateScheduled}</span></div></div>
        </div>
        <div class="col-sm-3">
            <strong>{translate text='Date Ran' isAdminFacing=true}</strong>
            <div class="row"><div class="col-sm-12">{if $updateRan}{$updateRan}{else}{translate text='Not Yet Ran' isAdminFacing=true}{/if}</div></div>
        </div>
	</div>
	<hr>
	<div class="row">
		<div class="col-sm-12">
			<strong>{translate text='Notes' isAdminFacing=true}</strong>
			<div class="row"><div class="col-sm-12">{if $updateNotes}{$updateNotes}{else}{translate text='None' isAdminFacing=true}{/if}</div></div>
		</div>
	</div>
{/strip}