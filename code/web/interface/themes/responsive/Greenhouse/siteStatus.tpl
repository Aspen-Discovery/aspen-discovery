{strip}
	<div class="row">
		<div class="col-xs-12 col-md-9">
			<h1 id="pageTitle">{$pageTitleShort}</h1>
		</div>
	</div>

	<div class='adminTableRegion'>
		<table class="adminTable table table-striped table-condensed smallText table-sticky" id="adminTable" aria-label="List of Objects">
			<thead>
				<tr>
					<th>Name</th>
					<th>Implementation Status</th>
					<th>Alive</th>
					{foreach from=$allChecks item=checkName key=checkType}
						<th>{$checkName}</th>
					{/foreach}
				</tr>
			</thead>
			<tbody>
				{foreach from=$siteStatuses item="siteStatus"}
					<tr>
						<td {if $siteStatus.aspen_health_status == 'okay'}style="background-color: lightgreen"{elseif $siteStatus.aspen_health_status == 'warning'}style="background-color: lightgoldenrodyellow"{else}style="background-color: #D50000;color:white;font-weight: bold"{/if}>
							{$siteStatus.name}
						</td>
						<td>
							{$siteStatus.implementationStatus}
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
											<a href="{$siteStatus.baseUrl}/OverDrive/IndexingLog" target="_blank">Log</a>
										{elseif $checkType == 'koha' || $checkType == 'carl_x' || $checkType == 'symphony' || $checkType == 'sierra' || $checkType == 'polaris'}
											<a href="{$siteStatus.baseUrl}/ILS/IndexingLog" target="_blank">Log</a>
										{elseif $checkType == 'axis_360'}
											<a href="{$siteStatus.baseUrl}/Axis360/IndexingLog" target="_blank">Log</a>
										{elseif $checkType == 'hoopla'}
											<a href="{$siteStatus.baseUrl}/Hoopla/IndexingLog" target="_blank">Log</a>
										{elseif $checkType == 'cloud_library'}
											<a href="{$siteStatus.baseUrl}/CloudLibrary/IndexingLog" target="_blank">Log</a>
										{elseif $checkType == 'web_indexer' || $checkType == 'web_builder'}
											<a href="{$siteStatus.baseUrl}/Websites/IndexingLog" target="_blank">Log</a>
										{elseif $checkType == 'cron'}
											<a href="{$siteStatus.baseUrl}/Admin/CronLog" target="_blank">Log</a>
										{elseif $checkType == 'nightly_index'}
											<a href="{$siteStatus.baseUrl}/Admin/ReindexLog" target="_blank">Log</a>
										{elseif $checkType == 'side_loads'}
											<a href="{$siteStatus.baseUrl}/SideLoads/IndexingLog" target="_blank">Log</a>
										{else}
											&nbsp;
										{/if}
									{else}
										&nbsp;
									{/if}
								</td>
							{else}
								<td>
									&nbsp;
								</td>
							{/if}
						{/foreach}
					</tr>
				{/foreach}
			</tbody>
		</table>
	</div>
{/strip}