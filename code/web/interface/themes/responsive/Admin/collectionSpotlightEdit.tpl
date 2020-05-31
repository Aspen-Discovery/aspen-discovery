{css filename="collectionSpotlight.css"}
{strip}
	<div id="main-content">
		<h1>Edit Collection Spotlight</h1>
		<div class="btn-group">
			<a class="btn btn-sm btn-default" href="/Admin/CollectionSpotlights">All Collection Spotlights</a>
			<a class="btn btn-sm btn-default" href="/Admin/CollectionSpotlights?objectAction=view&id={$object->id}">View</a>
			<a class="btn btn-sm btn-default" href="/API/SearchAPI?method=getCollectionSpotlight&id={$object->id}">Preview</a>
			{if $canDelete}
				<a class="btn btn-sm btn-danger" href="/Admin/CollectionSpotlights?objectAction=delete&id={$object->id}" onclick="return confirm('Are you sure you want to delete {$object->name}?');">Delete</a>
			{/if}
		</div>

		{$editForm}
	</div>
{/strip}