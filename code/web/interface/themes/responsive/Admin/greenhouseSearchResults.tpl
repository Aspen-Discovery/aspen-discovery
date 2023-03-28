<table id="greenhouseSearchResults" class="table table-striped table-bordered">
	<thead>
		<tr>
			<th>{translate text="Name" isAdminFacing=true}</th>
			<th>{translate text="Contibuted By" isAdminFacing=true}</th>
			<th>{translate text="Description" isAdminFacing=true}</th>
			<th></th>
		</tr>
	</thead>
	<tbody>
		{foreach from=$results item=$greenhouseSearchResult}
			<tr>
				<td>{$greenhouseSearchResult.name}</td>
				<td>{$greenhouseSearchResult.sharedFrom} {$greenhouseSearchResult.shareDate|date_format:"%D"}</td>
				<td>{$greenhouseSearchResult.description}</td>
				<td><a class="btn btn-default btn-sm" href="/{$toolModule}/{$toolName}?objectAction=importFromGreenhouse&objectType={$greenhouseSearchResult.type}&sourceId={$greenhouseSearchResult.id}">{translate text="Import" isAdminFacing=true}</a> </td>
			</tr>
		{/foreach}
	</tbody>
</table>