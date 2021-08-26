{strip}
	<div id="main-content" class="col-md-12">
		<h1>{translate text='Available Collection Spotlights' isAdminFacing=true}</h1>
		<div id="spotlights"></div>

		<div id="availableSpotlights">
			<table class="adminTable table table-striped table-condensed smallText" id="adminTable">
				<thead><tr><th>{translate text='Id' isAdminFacing=true}</th><th>{translate text='Name' isAdminFacing=true}</th><th>{translate text='Library' isAdminFacing=true}</th><th>{translate text='Description' isAdminFacing=true}</th><th>{translate text='Lists' isAdminFacing=true}</th><th class="sorter-false filter-false ">{translate text='Actions' isAdminFacing=true}</th></tr></thead>
				<tbody>
					{foreach from=$availableSpotlights key=id item=collectionSpotlight}
						<tr><td>{$collectionSpotlight->id}</td><td>{$collectionSpotlight->name}</td><td>{$collectionSpotlight->getLibraryName()}</td><td>{$collectionSpotlight->description}</td><td>{$collectionSpotlight->getListNames()}</td><td>
							<div class="btn-group-vertical btn-group-sm">
								<a class="btn btn-sm btn-default" href="/Admin/CollectionSpotlights?objectAction=view&id={$collectionSpotlight->id}" role="button">{translate text='View' isAdminFacing=true}</a>
								<a class="btn btn-sm btn-default" href="/Admin/CollectionSpotlights?objectAction=edit&id={$collectionSpotlight->id}" role="button">{translate text='Edit' isAdminFacing=true}</a>
								<a class="btn btn-sm btn-default" href="/API/SearchAPI?method=getCollectionSpotlight&id={$collectionSpotlight->id}" role="button">{translate text='Preview' isAdminFacing=true}</a>
								{if $canDelete}
									<a class="btn btn-sm btn-danger" href="/Admin/CollectionSpotlights?objectAction=delete&id={$collectionSpotlight->id}" role="button" onclick="return confirm('{translate text='Are you sure you want to delete %1%?' 1=$collectionSpotlight->name inAttribute=true isAdminFacing=true}');">{translate text='Delete' isAdminFacing=true}</a>
								{/if}
							</div>
						</td>
					{/foreach}
				</tbody>
			</table>
			{if $canAddNew}
				<input type="button" class="btn btn-primary" name="addCollectionSpotlight" value="{translate text='Add Collection Spotlight' inAttribute=true isAdminFacing=true}" onclick="window.location = '/Admin/CollectionSpotlights?objectAction=add';">
			{/if}
		</div>
	</div>
	{if !empty($availableSpotlights) && count($availableSpotlights) >= 3}
		<script type="text/javascript">
			{literal}
			$("#adminTable").tablesorter({cssAsc: 'sortAscHeader', cssDesc: 'sortDescHeader', cssHeader: 'unsortedHeader', widgets:['zebra', 'filter'] });
			{/literal}
		</script>
	{/if}
{/strip}