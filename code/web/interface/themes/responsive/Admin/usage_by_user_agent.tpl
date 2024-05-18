{strip}
	<div id="main-content" class="col-sm-12">
		<h1>{translate text="Aspen Discovery Usage By User Agent" isAdminFacing=true}</h1>
		{include file="Admin/selectInterfaceForm.tpl"}
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
	</div>
{/strip}
<script type="text/javascript">
{literal}
	$("#adminTable").tablesorter({cssAsc: 'sortAscHeader', cssDesc: 'sortDescHeader', cssHeader: 'unsortedHeader', widgets:['zebra'] });
{/literal}
</script>