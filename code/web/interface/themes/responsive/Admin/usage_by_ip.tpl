{strip}
	<div id="main-content" class="col-sm-12">
		<h1>{translate text="Aspen Discovery Usage By IP Address" isAdminFacing=true}</h1>
		{include file="Admin/selectInterfaceForm.tpl"}
		<table class="adminTable table table-striped table-condensed smallText table-sticky" id="adminTable" aria-label="{translate text="Statistics by IP Address" isAdminFacing=true inAttribute=true}">
			<thead>
				<tr>
					<th>{translate text="IP Address" isAdminFacing=true}</th>
					<th>{translate text="Total Requests" isAdminFacing=true}</th>
					<th>{translate text="Blocked Requests" isAdminFacing=true}</th>
					<th>{translate text="Blocked API Requests" isAdminFacing=true}</th>
					<th>{translate text="Login Attempts" isAdminFacing=true}</th>
					<th>{translate text="Failed Logins" isAdminFacing=true}</th>
					<th>{translate text="Last Request" isAdminFacing=true}</th>
				</tr>
			</thead>
			<tbody>
				{foreach from=$allIpStats item="ipStats"}
					<tr>
						<td>{$ipStats->ipAddress}</td>
						<td>{$ipStats->numRequests|number_format}</td>
						<td>{$ipStats->numBlockedRequests|number_format}</td>
						<td>{$ipStats->numBlockedApiRequests|number_format}</td>
						<td>{$ipStats->numLoginAttempts|number_format}</td>
						<td>{$ipStats->numFailedLoginAttempts|number_format}</td>
						<td>{$ipStats->lastRequest|date_format:"%D %T"}</td>
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