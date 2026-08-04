{strip}
<div class="container" style="max-width: 450px;">
	<div class="row">
		<div class="col-xs-12">
			<div class="text-center">
				<h1>{translate text="Authorization Error" isPublicFacing=true}</h1>
			</div>
			<div class="alert alert-danger">
				<strong>{translate text="Error:" isPublicFacing=true}</strong> {$error}<br>
				{$errorDescription}
			</div>

			<p>
				<a href="/" class="btn btn-default">
	                {translate text="Cancel" isPublicFacing=true}
				</a>
			</p>
		</div>
	</div>
</div>
{/strip}
