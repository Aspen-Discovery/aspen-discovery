{strip}
	{if $recordDriver}
		<div class="row">
			<div class="result-label col-xs-2">Grouped Work ID: </div>
			<div class="col-xs-10 result-value">
				{$recordDriver->getPermanentId()}
			</div>
		</div>

		<div class="row">
			<div class="col-xs-12">
				<a href="{$path}/GroupedWork/{$recordDriver->getPermanentId()}" class="btn btn-sm btn-default">Go To Grouped Work</a>
				<button onclick="return VuFind.Record.reloadCover('{$recordDriver->getModule()}', '{$id}')" class="btn btn-sm btn-default">Reload Cover</button>
				<button onclick="return VuFind.GroupedWork.reloadEnrichment('{$recordDriver->getPermanentId()}')" class="btn btn-sm btn-default" >Reload Enrichment</button>
				{if $loggedIn && (array_key_exists('opacAdmin', $userRoles) || array_key_exists('cataloging', $userRoles))}
					<button onclick="return VuFind.GroupedWork.forceReindex('{$recordDriver->getPermanentId()}')" class="btn btn-sm btn-default">Force Reindex</button>
					<button onclick="return VuFind.GroupedWork.forceRegrouping('{$recordDriver->getPermanentId()}')" class="btn btn-sm btn-default">Force Regrouping</button>
				{/if}
				{if $loggedIn && $enableArchive && (array_key_exists('opacAdmin', $userRoles) || array_key_exists('archives', $userRoles))}
					<button onclick="return VuFind.GroupedWork.reloadIslandora('{$recordDriver->getPermanentId()}')" class="btn btn-sm btn-default">Clear Islandora Cache</button>
				{/if}
			</div>
		</div>
	{/if}


	{if $rbdigitalExtract}
	<h3>Rbdigital Extract Information</h3>
	<pre>
		{$rbdigitalExtract|print_r}
	</pre>
	{/if}
{/strip}