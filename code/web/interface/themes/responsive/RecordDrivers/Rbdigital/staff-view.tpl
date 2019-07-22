{strip}
	{if $recordDriver}
		<div class="row">
			<div class="col-xs-12">
				<a href="{$path}/GroupedWork/{$recordDriver->getPermanentId()}" class="btn btn-sm btn-default">Go To Grouped Work</a>
				<button onclick="return AspenDiscovery.Record.reloadCover('{$recordDriver->getModule()}', '{$id}')" class="btn btn-sm btn-default">Reload Cover</button>
				<button onclick="return AspenDiscovery.GroupedWork.reloadEnrichment('{$recordDriver->getPermanentId()}')" class="btn btn-sm btn-default" >Reload Enrichment</button>
				{if $loggedIn && (array_key_exists('opacAdmin', $userRoles) || array_key_exists('cataloging', $userRoles))}
					<button onclick="return AspenDiscovery.GroupedWork.forceReindex('{$recordDriver->getPermanentId()}')" class="btn btn-sm btn-default">Force Reindex</button>
					<button onclick="return AspenDiscovery.GroupedWork.forceRegrouping('{$recordDriver->getPermanentId()}')" class="btn btn-sm btn-default">Force Regrouping</button>
				{/if}
				{if $loggedIn && $enableArchive && (array_key_exists('opacAdmin', $userRoles) || array_key_exists('archives', $userRoles))}
					<button onclick="return AspenDiscovery.GroupedWork.reloadIslandora('{$recordDriver->getPermanentId()}')" class="btn btn-sm btn-default">Clear Islandora Cache</button>
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

	{if $rbdigitalExtract}
	<h3>RBdigital Extract Information</h3>
	<pre>
		{$rbdigitalExtract|print_r}
	</pre>
	{/if}
{/strip}