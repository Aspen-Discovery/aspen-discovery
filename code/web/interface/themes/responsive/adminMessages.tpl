{if !empty($hasSqlUpdates)}
	<div id="admin-message-header" style="margin: 1em">
		<div class="alert alert-danger" id="admin-message" role="alert" aria-live="polite">
			<div class="admin-message-text">
				<strong><span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span> {translate text='Something broken? It looks like' isAdminFacing=true} <a href="/Admin/DBMaintenance" class="alert-link">{translate text='database maintenance' isAdminFacing=true}</a> {translate text='needs to be completed' isAdminFacing=true}</strong>
			</div>
		</div>
	</div>
{/if}
{if !empty($hasOptionalUpdates)}
	<div id="admin-message-header" style="margin: 1em">
		<div class="alert alert-warning" id="admin-message" role="alert" aria-live="polite">
			<div class="admin-message-text">
				<strong><a href="/Admin/OptionalUpdates"><span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span> {translate text='Recommended Updates Are Available' isAdminFacing=true}</a> </strong>
			</div>
		</div>
	</div>
{/if}