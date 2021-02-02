{strip}
	<div id="main-content" class="col-sm-12">
		<h1>{translate text="Aspen Discovery Usage By IP Address"}</h1>
		{include file="Admin/selectInterfaceForm.tpl"}
		<table class="adminTable table table-striped table-condensed smallText table-sticky" id="adminTable" aria-label="Statistics by IP Address">
			<thead>
				<tr>
					<th>{translate text="IP Address"}</th>
					<th>{translate text="Total Requests"}</th>
					<th>{translate text="Blocked Requests"}</th>
					<th>{translate text="Blocked API Requests"}</th>
					<th>{translate text="Login Attempts"}</th>
					<th>{translate text="Failed Logins"}</th>
					<th>{translate text="Last Request"}</th>
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