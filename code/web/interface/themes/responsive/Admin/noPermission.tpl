<div id="page-content" class="row">
	{if !empty($error)}<p class="alert alert-danger">{$error}</p>{/if}
	<div id="main-content" class="col-tn-12">
		<h1>{translate text='Ooops' isAdminFacing=true isPublicFacing=true}</h1>
		<div class="page">
			<div class="alert alert-warning">{translate text="We're sorry, but it looks like you don't have access to this page. If you think you should have access, please let us know." isAdminFacing=true isPublicFacing=true}</div>
		</div>
	</div>
</div>