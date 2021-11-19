{css filename="collectionSpotlight.css"}
{strip}
	<div id="main-content">
		<h1>{translate text="Edit Collection Spotlight" isAdminFacing=true}</h1>
		<div class="btn-group">
			<a class="btn btn-sm btn-default" href="/Admin/CollectionSpotlights">{translate text="All Collection Spotlights" isAdminFacing=true}</a>
			<a class="btn btn-sm btn-default" href="/Admin/CollectionSpotlights?objectAction=view&id={$object->id}">{translate text="View" isAdminFacing=true}</a>
			<a class="btn btn-sm btn-default" href="/API/SearchAPI?method=getCollectionSpotlight&id={$object->id}">{translate text="Preview" isAdminFacing=true}</a>
			{if $canDelete}
				<a class="btn btn-sm btn-danger" href="/Admin/CollectionSpotlights?objectAction=delete&id={$object->id}" onclick="return confirm('{translate text="Are you sure you want to delete %1%?" 1=$object->name inAttribute=true isAdminFacing=true}');"><i class="fas fa-trash"></i> {translate text="Delete" isAdminFacing=true}</a>
			{/if}
		</div>

		{$editForm}
	</div>
{/strip}