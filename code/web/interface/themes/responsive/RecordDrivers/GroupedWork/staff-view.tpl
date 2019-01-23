<strip>
<button onclick="return VuFind.GroupedWork.reloadCover('{$recordDriver->getPermanentId()}')" class="btn btn-sm btn-default">Reload Cover</button>
<button onclick="return VuFind.GroupedWork.reloadEnrichment('{$recordDriver->getPermanentId()}')" class="btn btn-sm btn-default">Reload Enrichment</button>
{if $loggedIn && (array_key_exists('opacAdmin', $userRoles) || array_key_exists('catalogging', $userRoles))}
	<button onclick="return VuFind.GroupedWork.forceReindex('{$recordDriver->getPermanentId()}')" class="btn btn-sm btn-default">Force Reindex</button>
	<button onclick="return VuFind.GroupedWork.forceRegrouping('{$recordDriver->getPermanentId()}')" class="btn btn-sm btn-default">Force Regrouping</button>
{/if}
{if $loggedIn && $enableArchive && (array_key_exists('opacAdmin', $userRoles) || array_key_exists('archives', $userRoles))}
	<button onclick="return VuFind.GroupedWork.reloadIslandora('{$recordDriver->getUniqueID()}')" class="btn btn-sm btn-default">Clear Islandora Cache</button>
{/if}


	{* QR Code *}
{if $showQRCode}
	<div id="record-qr-code" class="text-center hidden-xs visible-md"><img src="{$recordDriver->getQRCodeUrl()}" alt="QR Code for Record"></div>
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

<h4>Solr Details</h4>
<table class="table-striped table table-condensed notranslate">
	{foreach from=$details key='field' item='values'}
		<tr>
			<th>{$field|escape}</th>
			<td>
				{implode subject=$values glue=', ' sort=true}
			</td>
		</tr>
	{/foreach}
</table>
</strip>