<div id="main-content" class="col-md-12">
	<h1>{translate text="Active Tickets" isAdminFacing=true}</h1>
	<table class="adminTable table table-striped table-condensed smallText table-sticky" id="adminTable" aria-label="{translate text="Statistics by IP Address" isAdminFacing=true inAttribute=true}">
		<thead>
			<tr>
				<th>{translate text="ID" isAdminFacing=true}</th>
				<th>{translate text="Ticket Title" isAdminFacing=true}</th>
				<th>{translate text="Description" isAdminFacing=true}</th>
			</tr>
		</thead>
		<tbody>
			{foreach from=$activeTickets item="ticket"}
				<tr>
					<td>{$ticket.id}</td>
					<td><a href="{$ticket.link}" target="_blank">{$ticket.title}</a></td>
					<td>{$ticket.description}</td>
				</tr>
			{/foreach}
		</tbody>
	</table>
</div>