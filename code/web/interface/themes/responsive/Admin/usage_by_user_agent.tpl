{strip}
	<div id="main-content" class="col-sm-12">
		<h1>{translate text="Aspen Discovery Usage By User Agent" isAdminFacing=true}</h1>
		{include file="Admin/selectInterfaceForm.tpl"}
		<form class="form-inline row">
			<div class="form-group col-tn-12">
				<label for="pageSize" class="control-label">{translate text="Entries Per Page" isAdminFacing=true}</label>&nbsp;
				<select id="pageSize" name="pageSize" class="pageSize form-control input-sm" onchange="AspenDiscovery.changePageSize()">
					<option value="30"{if $recordsPerPage == 30} selected="selected"{/if}>30</option>
					<option value="50"{if $recordsPerPage == 50} selected="selected"{/if}>50</option>
					<option value="75"{if $recordsPerPage == 75} selected="selected"{/if}>75</option>
					<option value="100"{if $recordsPerPage == 100} selected="selected"{/if}>100</option>
				</select>
			</div>
		</form>
		<table class="adminTable table table-striped table-condensed smallText table-sticky" id="adminTable" aria-label="{translate text="Statistics by IP Address" isAdminFacing=true inAttribute=true}">
			<thead>
				<tr>
					<th>{translate text="User Agent" isAdminFacing=true}</th>
					<th>{translate text="Requests This Month" isAdminFacing=true}</th>
					<th>{translate text="Blocked This Month" isAdminFacing=true}</th>
					<th>{translate text="Requests Last Month" isAdminFacing=true}</th>
					<th>{translate text="Blocked Last Month" isAdminFacing=true}</th>
					<th>{translate text="Requests This Year" isAdminFacing=true}</th>
					<th>{translate text="Blocked This Year" isAdminFacing=true}</th>
					<th>{translate text="Requests Last Year" isAdminFacing=true}</th>
					<th>{translate text="Blocked Last Year" isAdminFacing=true}</th>
					<th>{translate text="Requests All Time" isAdminFacing=true}</th>
					<th>{translate text="Blocked All Time" isAdminFacing=true}</th>
				</tr>
			</thead>
			<tbody>
				{foreach from=$allUserAgents key=userAgentId item=userAgent}
					<tr>
						<td>{$userAgent|escape}</td>
						{if array_key_exists($userAgentId,$usageThisMonth)}
							<td>{$usageThisMonth.$userAgentId.numRequests}</td>
							<td>{$usageThisMonth.$userAgentId.numBlockedRequests}</td>
						{else}
							<td>0</td>
							<td>0</td>
						{/if}
						{if array_key_exists($userAgentId,$usageLastMonth)}
							<td>{$usageLastMonth.$userAgentId.numRequests}</td>
							<td>{$usageLastMonth.$userAgentId.numBlockedRequests}</td>
						{else}
							<td>0</td>
							<td>0</td>
						{/if}
						{if array_key_exists($userAgentId,$usageThisYear)}
							<td>{$usageThisYear.$userAgentId.numRequests}</td>
							<td>{$usageThisYear.$userAgentId.numBlockedRequests}</td>
						{else}
							<td>0</td>
							<td>0</td>
						{/if}
						{if array_key_exists($userAgentId,$usageLastYear)}
							<td>{$usageLastYear.$userAgentId.numRequests}</td>
							<td>{$usageLastYear.$userAgentId.numBlockedRequests}</td>
						{else}
							<td>0</td>
							<td>0</td>
						{/if}
						{if array_key_exists($userAgentId,$usageAllTime)}
							<td>{$usageAllTime.$userAgentId.numRequests}</td>
							<td>{$usageAllTime.$userAgentId.numBlockedRequests}</td>
						{else}
							<td>0</td>
							<td>0</td>
						{/if}
					</tr>
				{/foreach}
			</tbody>
		</table>
		{if !empty($pageLinks.all)}<div class="text-center">{$pageLinks.all}</div>{/if}
	</div>
{/strip}
<script type="text/javascript">
{literal}
	$("#adminTable").tablesorter({cssAsc: 'sortAscHeader', cssDesc: 'sortDescHeader', cssHeader: 'unsortedHeader', widgets:['zebra'] });
{/literal}
</script>