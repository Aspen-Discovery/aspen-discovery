{strip}
	<div class="row">
		<div class="col-xs-12 col-md-9">
			<h1 id="pageTitle">{$pageTitleShort}</h1>
		</div>
	</div>

	<div class="siteStatusRegion">
		<table class="table table-striped table-condensed smallText table-sticky" id="siteStatusTable" aria-label="List of sites with status">
			<thead>
				<tr>
					<th>Name</th>
					<th>Version</th>
					<th>Implementation Status</th>
					<th>Upgrade Command</th>
					<th>DB Maintenance</th>
				</tr>
			</thead>
			<tbody>
				{foreach from=$allSites item="site"}
					<tr>
						<td>
							<a href="{$site->baseUrl}" target="_blank">{$site->name}</a>
						</td>
						<td>
							{$site->getCurrentVersion()}
						</td>
						<td>
							{$site->getImplementationStatusName()}
						</td>
						<td>
							<input type="text" class="form-control" value="cd /usr/local/aspen-discovery/install; sudo ./upgrade.sh {$site->internalServerName} 21.10.00" onfocus="this.select()"/>
						</td>
						<td>
							<a href="{$site->baseUrl}/Admin/DBMaintenance" target="_blank">{translate text="Update"}</a>
						</td>
					</tr>
				{/foreach}
			</tbody>
		</table>
	</div>
{/strip}

<script type="text/javascript">
{literal}
	$("#siteStatusTable").tablesorter({cssAsc: 'sortAscHeader', cssDesc: 'sortDescHeader', cssHeader: 'unsortedHeader', widgets:['zebra', 'filter'] });
{/literal}
</script>