<div class="col-xs-12">
	<h1>{$title}</h1>
	{if $loggedIn && (array_key_exists('Administer All Basic Pages', $userPermissions) || array_key_exists('Administer Library Basic Pages', $userPermissions))}
		<div class="row">
			<div class="col-xs-12">
				<a href="/WebBuilder/BasicPages?id={$id}&objectAction=edit" class="btn btn-default btn-sm">{translate text=Edit}</a>
			</div>
		</div>
	{/if}
	{$contents}
</div>