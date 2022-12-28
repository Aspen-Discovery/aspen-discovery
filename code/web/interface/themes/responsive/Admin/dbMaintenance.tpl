{strip}
	<h1>{translate text="Database Maintenance" isAdminFacing=true}</h1>
	<div id="maintenanceOptions"></div>
	<form id="dbMaintenanceForm" action="/Admin/{$action}" method="post">
		<div>
			<table class="table" aria-label="List of Database Updates to Run">
				<thead>
					<tr>
						<th><input type="checkbox" id="selectAll" onclick="AspenDiscovery.toggleCheckboxes('.selectedUpdate:visible', '#selectAll');" checked="checked" title="Select All Rows"></th>
						<th>{translate text="Name" isAdminFacing=true}</th>
						<th>{translate text="Description" isAdminFacing=true}</th>
						<th>{translate text="Already Run?" isAdminFacing=true}</th>
						{if !empty($showStatus)}
						<th>{translate text="Status" isAdminFacing=true}</th>
						{/if}
					</tr>
				</thead>
				<tbody>
					{foreach from=$sqlUpdates item=update key=updateKey}
					<tr class="{if !empty($update.alreadyRun)}updateRun{else}updateNotRun{/if}
					{if empty($update.status)}
					{elseif $update.status == 'Update succeeded'} success
					{elseif strpos($update.status, 'Warning') !== false} warning
					{elseif strpos($update.status, 'fail') !== false || strpos($update.status, 'error') !== false} danger
					{/if}"
					{if !empty($update.alreadyRun) && empty($update.status)} style="display:none"{/if}>
						<td><input type="checkbox" name="selected[{$updateKey}]"{if empty($update.alreadyRun)} checked="checked"{/if} class="selectedUpdate" id="{$updateKey}"></td>
						<td><label for="{$updateKey}">{$update.title}</label></td>
						<td>{$update.description}</td>
						<td>{if !empty($update.alreadyRun)}{translate text="Yes" isAdminFacing=true}{else}{translate text="No" isAdminFacing=true}{/if}</td>
						{if !empty($showStatus)}
						<td>{if !empty($update.status)}{$update.status}{/if}</td>
						{/if}
					</tr>
					{/foreach}
				</tbody>
			</table>
			<div class="form-inline">
				<div class="form-group">
					{literal}
					<script type="text/javascript">
						var form = document.getElementById('dbMaintenanceForm');
						form.addEventListener('submit', submitDBMaintenance);
						function submitDBMaintenance() {
							$('#startDBUpdates').prop('disabled', true);
							$('#startDBUpdates').addClass('disabled');
							$('#startDBUpdates .fa-spinner').removeClass('hidden');
							return true;
						}
					</script>
					{/literal}
					<button type="submit" id="startDBUpdates" name="submit" class="btn btn-primary"><i class='fas fa-spinner fa-spin hidden' role='status' aria-hidden='true'></i>&nbsp;{translate text="Run Selected Updates" isAdminFacing=true}</button>
				</div>
				<div class="form-group checkbox checkbox-inline">
					&nbsp; &nbsp;
					<label for="hideUpdatesThatWereRun">
						<input type="checkbox" name="hideUpdatesThatWereRun" id="hideUpdatesThatWereRun" checked="checked"
						       onclick="$('.updateRun').toggle();"> {translate text="Hide updates that have been run" isAdminFacing=true}
					</label>
				</div>
			</div>
		</div>
	</form>
{/strip}