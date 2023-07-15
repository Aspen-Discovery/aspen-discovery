{include file="cssAndJsIncludes.tpl"}
{$themeCss}
<div class="container-fluid">
	<div id="content-container">
		{if !empty($error)}
			<h1>{translate text='Error Completing Payment' isPublicFacing=true}</h1>
		{else}
			<h1>{translate text='Payment Completed' isPublicFacing=true}</h1>
		{/if}
		<div class="row">
			<div class="col-xs-12">
				{if !empty($error)}
					<div class="alert alert-danger" id="errorMessage">{translate text=$error isPublicFacing=true}</div>
				{else}
					{if !empty($message)}
						<div class="alert alert-success" id="successMessage">{translate text=$message isPublicFacing=true}</div>
					{/if}
				{/if}
			</div>
		</div>
	</div>
</div>