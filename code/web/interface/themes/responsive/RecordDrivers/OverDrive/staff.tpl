{if $recordDriver}
	<div class="row">
		<div class="col-xs-12">
			<a href="{$path}/GroupedWork/{$recordDriver->getPermanentId()}" class="btn btn-sm btn-default">Go To Grouped Work</a>
			<button onclick="return AspenDiscovery.Record.reloadCover('{$recordDriver->getModule()}', '{$id}')" class="btn btn-sm btn-default">Reload Cover</button>
			<button onclick="return AspenDiscovery.GroupedWork.reloadEnrichment('{$recordDriver->getGroupedWorkId()}')" class="btn btn-sm btn-default" >Reload Enrichment</button>
			{if $loggedIn && (array_key_exists('opacAdmin', $userRoles) || array_key_exists('cataloging', $userRoles))}
				<button onclick="return AspenDiscovery.GroupedWork.forceReindex('{$recordDriver->getGroupedWorkId()}')" class="btn btn-sm btn-default">Force Reindex</button>
				<button onclick="return AspenDiscovery.GroupedWork.forceRegrouping('{$recordDriver->getGroupedWorkId()}')" class="btn btn-sm btn-default">Force Regrouping</button>
			{/if}
			{if $loggedIn && (array_key_exists('opacAdmin', $userRoles) || array_key_exists('archives', $userRoles))}
				<button onclick="return AspenDiscovery.GroupedWork.reloadIslandora('{$recordDriver->getGroupedWorkId()}')" class="btn btn-sm btn-default">Clear Islandora Cache</button>
			{/if}
		</div>
	</div>
{/if}

<h4>Grouping Information</h4>
<table class="table-striped table table-condensed notranslate">
	<tr>
		<th>Grouped Work ID</th>
		<td>{$recordDriver->getPermanentId()}</td>
	</tr>
	{foreach from=$groupedWorkDetails key='field' item='value'}
		<tr>
			<th>{$field|escape}</th>
			<td>
				{$value}
			</td>
		</tr>
	{/foreach}
</table>

<div class="row">
	<div class="result-label col-xs-3">Date Added: </div>
	<div class="col-xs-9 result-value">
		{$overDriveProduct->dateAdded|date_format:"%b %d, %Y %T"}
	</div>
</div>
<div class="row">
	<div class="result-label col-xs-3">Date Updated: </div>
	<div class="col-xs-9 result-value">
		{$overDriveProduct->dateUpdated|date_format:"%b %d, %Y %T"}
	</div>
</div>
<div class="row">
	{if $overDriveProduct->deleted}
		<div class="result-label col-xs-3">Deleted: </div>
		<div class="col-xs-9 result-value">
			{$overDriveProduct->dateDeleted|date_format:"%b %d, %Y %T"}
		</div>
	{/if}
</div>
<div class="row">
	<div class="result-label col-xs-3">Last Metadata Check: </div>
	<div class="col-xs-9 result-value">
		{$overDriveProduct->lastMetadataCheck|date_format:"%b %d, %Y %T"}
	</div>
</div>
<div class="row">
	<div class="result-label col-xs-3">Last Metadata Change: </div>
	<div class="col-xs-9 result-value">
		{$overDriveProduct->lastMetadataChange|date_format:"%b %d, %Y %T"}
	</div>
</div>
<div class="row">
	<div class="result-label col-xs-3">Last Availability Check: </div>
	<div class="col-xs-9 result-value">
		{$overDriveProduct->lastAvailabilityCheck|date_format:"%b %d, %Y %T"}
	</div>
</div>
<div class="row">
	<div class="result-label col-xs-3">Last Availability Change: </div>
	<div class="col-xs-9 result-value">
		{$overDriveProduct->lastAvailabilityChange|date_format:"%b %d, %Y %T"}
	</div>
</div>

{if $overDriveMetaDataRaw}
	<div id="formattedSolrRecord">
		<h3>OverDrive MetaData</h3>
		{formatJSON subject=$overDriveMetaDataRaw}
	</div>
{/if}