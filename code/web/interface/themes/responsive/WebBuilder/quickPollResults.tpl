<div class="col-xs-12">
	<h1>{$title}</h1>
	{if !empty($loggedIn) && (array_key_exists('Administer All Quick Polls', $userPermissions) || array_key_exists('Administer Library Quick Polls', $userPermissions))}
		<div class="row">
			<div class="col-xs-12">
				<a href="/WebBuilder/QuickPolls?id={$id}&objectAction=edit" class="btn btn-default btn-sm">{translate text=Edit isAdminFacing=true}</a>
			</div>
		</div>
	{/if}
	{if !empty($submissionResultText)}
		<div class="alert alert-success">
			{translate text=$submissionResultText isPublicFacing=true}
		</div>
	{elseif !empty($submissionError)}
		<div class="alert alert-danger">
            {translate text=$submissionError isPublicFacing=true}
		</div>
	{/if}
</div>