{strip}
	<div id="main-content" class="col-sm-12">
		<h1>{translate text="Website Page Statistics"}</h1>
		<h2>{$websiteName}</h2>
		<table class="table table-striped" id="page_stats">
			<thead>
				<tr>
					<th>{translate text="URL"}</th>
					<th>{translate text="In Search This Month"}</th>
					<th>{translate text="Clicked This Month"}</th>
					<th>{translate text="In Search Last Month"}</th>
					<th>{translate text="Clicked Last Month"}</th>
					<th>{translate text="In Search This Year"}</th>
					<th>{translate text="Clicked This Year"}</th>
					<th>{translate text="In Search All Time"}</th>
					<th>{translate text="Clicked All Time"}</th>
				</tr>
			</thead>
			<tbody>
			{foreach from=$pages item=url key=pageId}
				<tr>
					<td>{$url}</td>
					<td>{$activeRecordsThisMonth.$pageId.numRecordsViewed}</td>
					<td>{$activeRecordsThisMonth.$pageId.numRecordsUsed}</td>
					<td>{$activeRecordsLastMonth.$pageId.numRecordsViewed}</td>
					<td>{$activeRecordsLastMonth.$pageId.numRecordsUsed}</td>
					<td>{$activeRecordsThisYear.$pageId.numRecordsViewed}</td>
					<td>{$activeRecordsThisYear.$pageId.numRecordsUsed}</td>
					<td>{$activeRecordsAllTime.$pageId.numRecordsViewed}</td>
					<td>{$activeRecordsAllTime.$pageId.numRecordsUsed}</td>
				</tr>
			{/foreach}
			</tbody>
		</table>
	</div>
	<script type="text/javascript">
		$(document).ready(function () {literal} {
			$("#page_stats")
				.tablesorter({
					cssAsc: 'sortAscHeader',
					cssDesc: 'sortDescHeader',
					cssHeader: 'unsortedHeader',

				})
		});
		{/literal}
	</script>
{/strip}