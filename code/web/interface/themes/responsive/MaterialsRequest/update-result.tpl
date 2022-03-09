<div id="page-content" class="content">
	<div id="main-content">
		<h1>{translate text='Materials Request Update' isAdminFacing=true}</h1>
		{if $success == 0}
			<div class="alert alert-danger">
			{$error}
			</div>
		{else}
			<div class="alert alert-success">
			{translate text="The request for %1% by %2% was updated successfully." 1=$materialsRequest->title 2=$materialsRequest->author isAdminFacing=true}
			</div>
		{/if}
		<a role="button" class="btn btn-primary" href='/MaterialsRequest/ManageRequests'>{translate text="Return to Manage Requests" isAdminFacing=true}</a>.
	</div>
</div>
