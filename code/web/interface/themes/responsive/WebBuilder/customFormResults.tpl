<div class="col-xs-12">
	<h1>{$title}</h1>
	{if $loggedIn && (array_key_exists('opacAdmin', $userRoles) || array_key_exists('web_builder_admin', $userRoles) || array_key_exists('web_builder_creator', $userRoles))}
		<div class="row">
			<div class="col-xs-12">
				<a href="/WebBuilder/CustomForms?id={$id}&objectAction=edit" class="btn btn-default btn-sm">{translate text=Edit}</a>
			</div>
		</div>
	{/if}
	{if !empty($submissionResultText)}
		<div class="alert alert-success">
			{$submissionResultText}
		</div>
	{elseif !empty($submissionError)}
		<div class="alert alert-success">
			{$submissionError}
		</div>
	{/if}
</div>