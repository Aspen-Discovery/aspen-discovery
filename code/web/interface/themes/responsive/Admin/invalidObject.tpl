<div id="page-content" class="row">
    {if !empty($error)}<p class="alert alert-danger">{$error}</p>{/if}
	<div id="main-content" class="col-tn-12">
		<h1>{translate text='Ooops' isAdminFacing=true isPublicFacing=true}</h1>
		<div class="page">
			<div class="alert alert-warning">{translate text="We're sorry, we can't find an object with that ID." isAdminFacing=true isPublicFacing=true}</div>
		</div>
	</div>
</div>