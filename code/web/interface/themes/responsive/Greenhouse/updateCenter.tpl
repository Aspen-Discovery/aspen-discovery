{strip}
	<div class="row">
		<div class="col-xs-12 col-md-9">
			<h1 id="pageTitle">{$pageTitleShort}</h1>
		</div>
	</div>

	<form class="form well" id="updateCenterFilters" style="padding-bottom:1em">
		<div class="row align-middle">
			<div class="col-xs-12 col-md-3">
				<div class="form-group">
					<label for="implementationStatusToShow">{translate text='Implementation Status' isAdminFacing=true}</label>
					<select name="implementationStatusToShow" id="implementationStatusToShowSelect" class="form-control">
						<option value="any"{if !empty($implementationStatusToShow) && ($implementationStatusToShow == 'any')} selected='selected'{/if}>Any</option>
						{foreach from=$implementationStatuses item=status key=index}
							<option value="{$index}"{if !empty($implementationStatusToShow) && ($implementationStatusToShow == $index)} selected='selected'{/if}>{$status}</option>
						{/foreach}
					</select>
				</div>
			</div>
			<div class="col-xs-12 col-md-2">
				<div class="form-group">
					<label for="siteTypeToShow">{translate text='Site Type' isAdminFacing=true}</label>
					<select name="siteTypeToShow" id="siteTypeToShowSelect" class="form-control">
						<option value="any"{if !empty($siteTypeToShow) && ($siteTypeToShow == 'any')} selected='selected'{/if}>Any</option>
						{foreach from=$siteTypes item=type key=index}
							<option value="{$index}"{if !empty($siteTypeToShow) && ($siteTypeToShow == $index)} selected='selected'{/if}>{$type}</option>
						{/foreach}
					</select>
				</div>
			</div>
			<div class="col-xs-12 col-md-2">
				<div class="form-group">
					<label for="releaseToShow">{translate text='Version' isAdminFacing=true}</label>
					<select name="releaseToShow" id="releaseToShowSelect" class="form-control">
						<option value="any"{if !empty($releaseToShow) && ($releaseToShow == 'any')} selected='selected'{/if}>Any</option>
						{foreach from=$releases item=release}
							<option value="{$release.version}"{if !empty($releaseToShow) && ($releaseToShow == $release.version)} selected='selected'{/if}>{$release.name}</option>
						{/foreach}
					</select>
				</div>
			</div>
			<div class="col-xs-12 col-md-2">
				<div class="form-group">
					<label for="timezoneToShow">{translate text='Timezone' isAdminFacing=true}</label>
					<select name="timezoneToShow" id="timezoneToShowSelect" class="form-control">
						<option value="any"{if !empty($timezoneToShow) && ($timezoneToShow == 'any')} selected='selected'{/if}>Any</option>
						{foreach from=$timezones item=timezone key=index}
							<option value="{$index}"{if !empty($timezoneToShow) && ($timezoneToShow == $index)} selected='selected'{/if}>{$timezone}</option>
						{/foreach}
					</select>
				</div>
			</div>
			<div class="col-xs-12 col-md-3">
				<div class="btn-group btn-group-sm btn-group-justified" role="group">
					<div class="btn-group" role="group">
						<button class="btn btn-primary" type="submit">{translate text="Apply" isAdminFacing=true}</button>
					</div>
					<div class="btn-group" role="group">
						<a class="btn btn-default" href="{$url}/Greenhouse/UpdateCenter">{translate text="Reset" isAdminFacing=true}</a>
					</div>
				</div>
			</div>
		</div>
	</form>

	{if $allSites|@count gt 0}
	<div class="row">
		<div class="col-xs-12">
			<div class="btn-toolbar" role="toolbar">
				<div class="btn-group" role="group">
					<a onclick="return AspenDiscovery.Admin.showSelectedScheduleUpdateForm();" class="btn btn-warning">{translate text="Schedule Update for Selected" isAdminFacing=true}</a>
					<a onclick="return AspenDiscovery.Admin.showBatchScheduleUpdateForm('{$implementationStatusToShow}', '{$siteTypeToShow}', '{$releaseToShow}', '{$timezoneToShow}');" class="btn btn-warning">{translate text="Schedule Update for All Listed" isAdminFacing=true}</a>
				</div>
			</div>
		</div>
	</div>
	{/if}

	<div class="siteStatusRegion">
		<table class="table table-striped table-condensed smallText table-sticky" id="siteStatusTable" aria-label="{translate text="List of sites to update" inAttribute=true isAdminFacing=true}">
			<thead>
				<tr>
					<th width="5%"></th>
					<th>{translate text="Name" isAdminFacing=true}</th>
					<th>{translate text="Version" isAdminFacing=true}</th>
					<th>{translate text="Site Type" isAdminFacing=true}</th>
					<th>{translate text="Timezone" isAdminFacing=true}</th>
					<th>{translate text="Implementation Status" isAdminFacing=true}</th>
					<th>{translate text="Hosting" isAdminFacing=true}</th>
					<th>{translate text="Last Scheduled Update" isAdminFacing=true}</th>
					<th>{translate text="Last Ran Update" isAdminFacing=true}</th>
					<th>{translate text="DB Maintenance" isAdminFacing=true}</th>
				</tr>
			</thead>
			<tbody>
				{foreach from=$allSites item="site"}
					<tr>
						<td>
							{if !$site->optOutBatchUpdates}
								<input type="checkbox" class="siteSelect" name="{$site->id}" id="{$site->id}">
							{/if}
						</td>
						<td>
							<a href="{$site->baseUrl}" target="_blank">{$site->name}</a>
						</td>
						<td>
							{$site->version}<br>
							<a class="btn btn-xs btn-warning" onclick="return AspenDiscovery.Admin.showScheduleUpdateForm('{$site->id}');"><i class="fas fa-clock"></i> {translate text="Schedule Update" isAdminFacing=true}</a>
						</td>
						<td>
							{translate text=$site->getSiteTypeName() isAdminFacing=true}
						</td>
						<td>
							{translate text=$site->getTimezoneName() isAdminFacing=true}
						</td>
						<td>
							{translate text=$site->getImplementationStatusName() isAdminFacing=true}
						</td>
						<td>
							{$site->hosting}
						</td>
						<td>
						{assign var='lastScheduledUpdate' value=$site->getLastScheduledUpdate()}
							{if $lastScheduledUpdate['time'] !== 'Never'}
								<a onclick="return AspenDiscovery.Admin.showScheduledUpdateDetails('{$lastScheduledUpdate['id']}');">{$lastScheduledUpdate['time']|date_format:"%D %T"}</a>
							{else}
								{$lastScheduledUpdate['time']}
							{/if}
						</td>
						<td>
							{assign var='lastRanUpdate' value=$site->getLastRanUpdate()}
							{if $lastRanUpdate['time'] !== 'Never'}
								<span class="label {if $lastRanUpdate['status'] == 'pending'}label-warning{elseif $lastRanUpdate['status'] == 'failed'}label-danger{elseif $lastRanUpdate['status'] == 'complete'}label-success{else}label-default{/if}">{$lastRanUpdate['status']}</span><br>
								<a onclick="return AspenDiscovery.Admin.showScheduledUpdateDetails('{$lastRanUpdate['id']}');">{$lastRanUpdate['time']|date_format:"%D %T"}</a>
							{else}
								{$lastRanUpdate['time']}
							{/if}
						</td>
						<td>
							<a class="btn btn-xs btn-default" href="{$site->baseUrl}/Admin/DBMaintenance" target="_blank"><i class="fas fa-external-link-alt" role="presentation"></i> {translate text="Run" isAdminFacing=true}</a>
						</td>
					</tr>
				{/foreach}
			</tbody>
		</table>
	</div>
{/strip}

<script type="text/javascript">
{literal}
	$("#siteStatusTable").tablesorter({cssAsc: 'sortAscHeader', cssDesc: 'sortDescHeader', cssHeader: 'unsortedHeader', widgets:['zebra', 'filter']});
{/literal}
</script>
