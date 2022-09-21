<form id="batchDeleteForm" class="form-horizontal" role="form">

	{if $batchScope === "all"}
		<div class="form-group col-xs-12">
            <p class="alert alert-danger">Are you sure you want to delete {$numObjects} objects?</p>
    	</div>
	{else}
		<div class="form-group col-xs-12">
            <p class="alert alert-danger">Are you sure you want to delete the selected objects?</p>
    	</div>
	{/if}

</form>