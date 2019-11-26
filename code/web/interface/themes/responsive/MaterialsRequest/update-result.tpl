<div id="page-content" class="content">
	<div id="main-content">
		<h1>{translate text='Materials Request Update'}</h1>
		{if $success == 0}
			<div class="alert alert-danger">
			{$error}
			</div>
		{else}
			<div class="alert alert-success">
			The request for {$materialsRequest->title} by {$materialsRequest->author} was updated successfully.
			</div>
		{/if}
		<a role="button" class="btn btn-primary" href='/MaterialsRequest/ManageRequests'>Return to Manage Requests</a>.
	</div>
</div>
