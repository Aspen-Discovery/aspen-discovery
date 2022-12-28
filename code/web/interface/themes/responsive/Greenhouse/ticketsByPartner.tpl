{strip}
	<div id="main-content" class="col-sm-12">
		<h1>{translate text="Active Tickets By Partner" isAdminFacing=true}</h1>

		<table id="adminTable" class="table table-striped table-bordered">
			<thead>
			<tr>
				<th>{translate text="Site Name" isAdminFacing=true}</th>
				<th>{translate text="Implementation" isAdminFacing=true}</th>
				<th>{translate text="Support" isAdminFacing=true}</th>
				<th>{translate text="Bugs" isAdminFacing=true}</th>
				<th>{translate text="Development" isAdminFacing=true}</th>
				<th>{translate text="Total" isAdminFacing=true}</th>
			</tr>
			</thead>
			<tbody>
				{foreach from=$ticketsByPartner item=partnerTicketInfo}
					<tr>
						<td><a href="/Greenhouse/Tickets?sort=ticketId+desc&filterType[showClosedTickets]=matches&filterValue[showClosedTickets]=0&filterType[requestingPartner]=matches&filterValue[requestingPartner]={$partnerTicketInfo.siteId}&page=&pageSize=25&objectAction=list">{$partnerTicketInfo.siteName}</a></td>
						<td>{if $partnerTicketInfo.Implementation > 0}<a href="/Greenhouse/Tickets?sort=ticketId+desc&filterType[showClosedTickets]=matches&filterValue[showClosedTickets]=0&filterType[requestingPartner]=matches&filterValue[requestingPartner]={$partnerTicketInfo.siteId}&filterType[queue]=matches&filterValue[queue]=Implementation&page=&pageSize=25&objectAction=list">{/if}{$partnerTicketInfo.Implementation}{if $partnerTicketInfo.Implementation > 0}</a>{/if}</td>
						<td>{if $partnerTicketInfo.Support > 0}<a href="/Greenhouse/Tickets?sort=ticketId+desc&filterType[showClosedTickets]=matches&filterValue[showClosedTickets]=0&filterType[requestingPartner]=matches&filterValue[requestingPartner]={$partnerTicketInfo.siteId}&filterType[queue]=matches&filterValue[queue]=Support&page=&pageSize=25&objectAction=list">{/if}{$partnerTicketInfo.Support}{if $partnerTicketInfo.Support > 0}</a>{/if}</td>
						<td>{if $partnerTicketInfo.Bugs > 0}<a href="/Greenhouse/Tickets?sort=ticketId+desc&filterType[showClosedTickets]=matches&filterValue[showClosedTickets]=0&filterType[requestingPartner]=matches&filterValue[requestingPartner]={$partnerTicketInfo.siteId}&filterType[queue]=matches&filterValue[queue]=Bugs&page=&pageSize=25&objectAction=list">{/if}{$partnerTicketInfo.Bugs}{if $partnerTicketInfo.Bugs > 0}</a>{/if}</td>
						<td>{if $partnerTicketInfo.Development > 0}<a href="/Greenhouse/Tickets?sort=ticketId+desc&filterType[showClosedTickets]=matches&filterValue[showClosedTickets]=0&filterType[requestingPartner]=matches&filterValue[requestingPartner]={$partnerTicketInfo.siteId}&filterType[queue]=matches&filterValue[queue]=Development&page=&pageSize=25&objectAction=list">{/if}{$partnerTicketInfo.Development}{if $partnerTicketInfo.Development > 0}</a>{/if}</td>
						<td>{$partnerTicketInfo.Total}</td>
					</tr>
				{/foreach}
			</tbody>
		</table>
	</div>
{/strip}
<script type="text/javascript">
	{literal}
	$("#adminTable").tablesorter({cssAsc: 'sortAscHeader', cssDesc: 'sortDescHeader', cssHeader: 'unsortedHeader', widgets:['zebra', 'filter'] });
	{/literal}
</script>