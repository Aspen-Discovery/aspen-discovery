{strip}
	<div id="page-content" class="col-xs-12">
	<h1>{translate text='Forgot %1%' 1=$usernameLabel isPublicFacing=true translateParameters=true}</h1>
		{if !$result.success && $result.message}
			<div class="alert alert-danger">{$result.message}</div>
			<div>
				<a class="btn btn-primary" role="button" href="/MyAccount/ForgotBarcode">{translate text='Try Again' isPublicFacing=true}</a>
			</div>
		{else}
			<p class="alert alert-success">{$result.message}</p>
			<p class="alert alert-warning">
				{translate text="If you do not receive an text within a few minutes, please contact your library to have them retrieve your barcode." isPublicFacing=true}
			</p>
        {/if}
	</div>
{/strip}