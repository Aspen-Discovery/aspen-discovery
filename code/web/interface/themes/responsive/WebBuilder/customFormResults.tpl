<div class="col-xs-12">
	<h1>{$title}</h1>
	{if !empty($loggedIn) && (array_key_exists('Administer All Custom Forms', $userPermissions) || array_key_exists('Administer Library Custom Forms', $userPermissions))}
		<div class="row">
			<div class="col-xs-12">
				<a href="/WebBuilder/CustomForms?id={$id}&objectAction=edit" class="btn btn-default btn-sm">{translate text=Edit isAdminFacing=true}</a>
			</div>
		</div>
	{/if}
	{if !empty($submissionResultText)}
		<div class="alert alert-success">
			{$submissionResultText}
		</div>
	{elseif !empty($submissionError)}
		<div class="alert alert-danger">
			{$submissionError}
		</div>
	{/if}
</div>