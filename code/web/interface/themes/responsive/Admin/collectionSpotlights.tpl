{strip}
	<div id="main-content" class="col-md-12">
		<h3>Available Collection Spotlights</h3>
		<div id="spotlights"></div>

		<div id="availableSpotlights">
		<table class="table table-striped">
		<thead><tr><th>Id</th><th>Name</th><th>Library</th><th>Description</th><th>Lists</th><th class="sorter-false filter-false ">Actions</th></tr></thead>
		<tbody>
			{foreach from=$availableSpotlights key=id item=collectionSpotlight}
				<tr><td>{$collectionSpotlight->id}</td><td>{$collectionSpotlight->name}</td><td>{$collectionSpotlight->getLibraryName()}</td><td>{$collectionSpotlight->description}</td><td>{$collectionSpotlight->getListNames()}</td><td>
					<div class="btn-group-vertical btn-group-sm">
						<a class="btn btn-sm btn-default" href="/Admin/CollectionSpotlights?objectAction=view&id={$collectionSpotlight->id}" role="button">View</a>
						<a class="btn btn-sm btn-default" href="/Admin/CollectionSpotlights?objectAction=edit&id={$collectionSpotlight->id}" role="button">Edit</a>
						<a class="btn btn-sm btn-default" href="/API/SearchAPI?method=getCollectionSpotlight&id={$collectionSpotlight->id}" role="button">Preview</a>
						{if $canDelete}
							<a class="btn btn-sm btn-danger" href="/Admin/CollectionSpotlights?objectAction=delete&id={$collectionSpotlight->id}" role="button" onclick="return confirm('Are you sure you want to delete {$collectionSpotlight->name}?');">Delete</a>
						{/if}
					</div>
				</td>
			{/foreach}
		</tbody>
		</table>
		{if $canAddNew}
			<input type="button" class="btn btn-primary" name="addCollectionSpotlight" value="Add Collection Spotlight" onclick="window.location = '/Admin/CollectionSpotlights?objectAction=add';">
		{/if}
		</div>
	</div>
	{if !empty($availableSpotlights) && count($availableSpotlights) > 5}
		<script type="text/javascript">
			{literal}
			$("#availableSpotlights>table").tablesorter({cssAsc: 'sortAscHeader', cssDesc: 'sortDescHeader', cssHeader: 'unsortedHeader', widgets:['zebra', 'filter'] });
			{/literal}
		</script>
	{/if}
{/strip}