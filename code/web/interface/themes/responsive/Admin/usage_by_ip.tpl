{strip}
	<div id="main-content" class="col-sm-12">
		<h1>{translate text="Aspen Discovery Usage By IP Address"}</h1>
		<table class="adminTable table table-striped table-condensed smallText table-sticky" id="adminTable" aria-label="Statistics by IP Address">
			<thead>
				<tr>
					<th>{translate text="IP Address"}</th>
					<th>{translate text="Total Requests"}</th>
					<th>{translate text="Blocked Requests"}</th>
					<th>{translate text="Blocked API Requests"}</th>
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
						<td>{$ipStats->lastRequest|date_format:"%D %T"}</td>
					</tr>
				{/foreach}
			</tbody>
		</table>
	</div>
{/strip}