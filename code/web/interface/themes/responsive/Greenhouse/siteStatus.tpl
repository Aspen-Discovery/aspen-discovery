{strip}
	<div class="row">
		<div class="col-xs-12 col-md-9">
			<h1 id="pageTitle">{$pageTitleShort}</h1>
		</div>
	</div>

	<form class="form" id="translationSettings">
		<div class="row">
			<div class="col-xs-12">
				<label for="showErrorsOnly">{translate text='Show Errors Only' isAdminFacing=true}</label>
				<div class="input-group-sm input-group">
					<input type='checkbox' name='showErrorsOnly' id='showErrorsOnly' data-on-text="{translate text='Errors Only' inAttribute=true isAdminFacing=true}" data-off-text="{translate text='All Records' inAttribute=true isAdminFacing=true}" data-switch="" {if $showErrorsOnly}checked{/if}/>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-sm-2 col-md-4">
				<div class="form-group">
					<button class="btn btn-primary btn-sm" type="submit">{translate text="Apply" isAdminFacing=true}</button>
				</div>
			</div>
		</div>
		<script type="text/javascript">
			{literal}
			$(function(){ $('input[type="checkbox"][data-switch]').bootstrapSwitch()});
			{/literal}
		</script>
	</form>

	<div class="siteStatusRegion">
		<table class="table table-striped table-condensed smallText table-sticky" id="siteStatusTable" aria-label="{translate text="List of sites with status" inAttribute=true isAdminFacing=true}">
			<thead>
				<tr>
					<th>{translate text="Name" isAdminFacing=true}</th>
					<th>{translate text="DB Maintenance" isAdminFacing=true}</th>
					<th>{translate text="Implementation Status" isAdminFacing=true}</th>
					<th>{translate text="Timezone" isAdminFacing=true}</th>
					<th>{translate text="Version" isAdminFacing=true}</th>
					{foreach from=$allChecks item=checkName key=checkType}
						{if !$showErrorsOnly || array_key_exists($checkType,$checksWithErrors)}
							<th>{translate text=$checkName isAdminFacing=true}</th>
						{/if}
					{/foreach}
				</tr>
			</thead>
			<tbody>
				{foreach from=$siteStatuses item="siteStatus"}
					{if !$showErrorsOnly || array_key_exists($siteStatus.name,$sitesWithErrors)}
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
                            {translate text=$siteStatus.timezone isAdminFacing=true}
						</td>
						<td>
							{$siteStatus.version}
						</td>
						{foreach from=$allChecks item=checkName key=checkType}
							{if !$showErrorsOnly || array_key_exists($checkType,$checksWithErrors)}
								{if array_key_exists($checkType,$siteStatus.checks)}
									{assign var="checks" value=$siteStatus.checks}
									{assign var="check" value=$checks.$checkType}
									<td {if $check.status == 'okay'}style="background-color: lightgreen;text-align: center"{elseif $check.status == 'warning'}style="background-color: lightpink;text-align: center"{else}style="background-color: #D50000;color:white;font-weight: bold;text-align: center"{/if} {if !empty($check.note)}title="{$check.note|escape:css}" {/if}>
										{if !empty($check.url)}<a href="{$check.url}" target="_blank">{/if}{translate text=$check.status isAdminFacing=true}{if !empty($check.url)}</a>{/if}
									</td>
								{else}
									<td>
										-
									</td>
								{/if}
							{/if}
						{/foreach}
					</tr>
					{/if}
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