{strip}
	<div class="row">
		<div class="col-xs-12 col-md-9">
			<h1 id="pageTitle">{$pageTitleShort}</h1>
		</div>
	</div>

	<div class="siteStatusRegion">
		<table class="table table-striped table-condensed smallText table-sticky" id="siteStatusTable" aria-label="{translate text="List of sites with status" inAttribute=true isAdminFacing=true}">
			<thead>
				<tr>
					<th>{translate text="Name" isAdminFacing=true}</th>
					<th>{translate text="DB Maintenance" isAdminFacing=true}</th>
					<th>{translate text="Implementation Status" isAdminFacing=true}</th>
					<th>{translate text="Version" isAdminFacing=true}</th>
					<th>{translate text="Alive" isAdminFacing=true}</th>
					{foreach from=$allChecks item=checkName key=checkType}
						<th>{translate text=$checkName isAdminFacing=true}</th>
					{/foreach}
				</tr>
			</thead>
			<tbody>
				{foreach from=$siteStatuses item="siteStatus"}
					<tr>
						<td {if $siteStatus.aspen_health_status == 'okay'}style="background-color: lightgreen"{elseif $siteStatus.aspen_health_status == 'warning'}style="background-color: lightgoldenrodyellow"{else}style="background-color: #D50000;color:white;font-weight: bold"{/if}>
							<a href="{$siteStatus.baseUrl}" target="_blank">{$siteStatus.name}</a>
						</td>
						<td>
							<a href="{$siteStatus.baseUrl}/Admin/DBMaintenance" target="_blank">{translate text="Update" isAdminFacing=true}</a>
						</td>
						<td>
							{translate text=$siteStatus.implementationStatus isAdminFacing=true}
						</td>
						<td>
							{$siteStatus.version}
						</td>
						<td {if $siteStatus.alive}style="background-color: lightgreen"{else}style="background-color: #D50000;color:white;font-weight: bold"{/if}>
							&nbsp;
						</td>
						{foreach from=$allChecks item=checkName key=checkType}
							{if array_key_exists($checkType,$siteStatus.checks)}
								{assign var="checks" value=$siteStatus.checks}
								{assign var="check" value=$checks.$checkType}
								<td {if $check.status == 'okay'}style="background-color: lightgreen;text-align: center"{elseif $check.status == 'warning'}style="background-color: lightpink;text-align: center"{else}style="background-color: #D50000;color:white;font-weight: bold;text-align: center"{/if} {if !empty($check.note)}title="{$check.note|escape:css}" {/if}>
									{if $check.status != 'okay'}
										{if $checkType == 'overdrive'}
											<a href="{$siteStatus.baseUrl}/OverDrive/IndexingLog" target="_blank">{translate text=$check.status isAdminFacing=true}</a>
										{elseif $checkType == 'koha' || $checkType == 'carl.x' || $checkType == 'symphony' || $checkType == 'sierra' || $checkType == 'polaris'}
											<a href="{$siteStatus.baseUrl}/ILS/IndexingLog" target="_blank">{translate text=$check.status isAdminFacing=true}</a>
										{elseif $checkType == 'axis_360'}
											<a href="{$siteStatus.baseUrl}/Axis360/IndexingLog" target="_blank">{translate text=$check.status isAdminFacing=true}</a>
										{elseif $checkType == 'hoopla'}
											<a href="{$siteStatus.baseUrl}/Hoopla/IndexingLog" target="_blank">{translate text=$check.status isAdminFacing=true}</a>
										{elseif $checkType == 'cloud_library'}
											<a href="{$siteStatus.baseUrl}/CloudLibrary/IndexingLog" target="_blank">{translate text=$check.status isAdminFacing=true}</a>
										{elseif $checkType == 'web_indexer' || $checkType == 'web_builder'}
											<a href="{$siteStatus.baseUrl}/Websites/IndexingLog" target="_blank">{translate text=$check.status isAdminFacing=true}</a>
										{elseif $checkType == 'cron'}
											<a href="{$siteStatus.baseUrl}/Admin/CronLog" target="_blank">{translate text=$check.status isAdminFacing=true}</a>
										{elseif $checkType == 'nightly_index'}
											<a href="{$siteStatus.baseUrl}/Admin/ReindexLog" target="_blank">{translate text=$check.status isAdminFacing=true}</a>
										{elseif $checkType == 'side_loads'}
											<a href="{$siteStatus.baseUrl}/SideLoads/IndexingLog" target="_blank">{translate text=$check.status isAdminFacing=true}</a>
                                        {elseif $checkType == 'nyt_lists'}
											<a href="{$siteStatus.baseUrl}/UserLists/NYTUpdatesLog" target="_blank">{translate text=$check.status isAdminFacing=true}</a>
                                        {else}
											{translate text=$check.status isAdminFacing=true}
										{/if}
									{else}
										{translate text=$check.status isAdminFacing=true}
									{/if}
								</td>
							{else}
								<td>
									-
								</td>
							{/if}
						{/foreach}
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