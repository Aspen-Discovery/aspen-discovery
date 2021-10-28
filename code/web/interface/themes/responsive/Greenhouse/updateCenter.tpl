{strip}
	<div class="row">
		<div class="col-xs-12 col-md-9">
			<h1 id="pageTitle">{$pageTitleShort}</h1>
		</div>
	</div>

	<p class="alert alert-info">
		{translate text="Quick Update to current version" isAdminFacing=true}
		<pre>
			cd /usr/local/aspen-discovery; sudo git pull origin {$gitBranch}
		</pre>
	</p>

	<div class="siteStatusRegion">
		<table class="table table-striped table-condensed smallText table-sticky" id="siteStatusTable" aria-label="{translate text="List of sites to upgrade" inAttribute=true isAdminFacing=true}">
			<thead>
				<tr>
					<th>{translate text="Name" isAdminFacing=true}</th>
					<th>{translate text="Version" isAdminFacing=true}</th>
					<th>{translate text="Implementation Status" isAdminFacing=true}</th>
					<th>{translate text="Hosting" isAdminFacing=true}</th>
					<th>{translate text="Full Update" isAdminFacing=true}</th>
					<th>{translate text="DB Maintenance" isAdminFacing=true}</th>
				</tr>
			</thead>
			<tbody>
				{foreach from=$allSites item="site"}
					<tr>
						<td>
							<a href="{$site->baseUrl}" target="_blank">{$site->name}</a>
						</td>
						<td>
							{$site->version}
						</td>
						<td>
							{translate text=$site->getImplementationStatusName() isAdminFacing=true}
						</td>
						<td>
                            {$site->hosting}
						</td>
						<td>
							<input type="text" class="form-control" value="cd /usr/local/aspen-discovery/install; sudo ./upgrade.sh {$site->internalServerName} {$gitBranch}" onfocus="this.select()"/>
						</td>
						<td>
							<a href="{$site->baseUrl}/Admin/DBMaintenance" target="_blank">{translate text="Update" isAdminFacing=true}</a>
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