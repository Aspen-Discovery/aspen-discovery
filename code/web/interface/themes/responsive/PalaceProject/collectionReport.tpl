<div id="main-content" class="col-md-12">
	<h1>{translate text="Collection Report" isAdminFacing=true}</h1>
	{foreach from=$allLibraries item="tmpLibrary"}
		<h2>{$tmpLibrary.displayName}</h2>
		<table class="table table-striped">
			<thead>
				<tr>
					<th>Palace Project Name</th>
					<th>Display Name</th>
					<th>Active titles</th>
					<th>Deleted titles</th>
				</tr>
			</thead>
			<tbody>
				{foreach from=$tmpLibrary.collections item="collection"}
					<tr>
						<td>{$collection.palaceProjectName}</td>
						<td>{$collection.displayName}</td>
						<td style="text-align: right">{$collection.numTitles|number_format}</td>
						<td style="text-align: right">{$collection.numDeletedTitles|number_format}</td>
					</tr>
				{/foreach}
			</tbody>
		</table>
	{foreachelse}
		<h2>No libraries are active for Palace Project</h2>
	{/foreach}
</div>