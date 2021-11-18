{if $hasSqlUpdates}
	<div id="admin-message-header" style="margin: 1em">
		<div class="alert alert-danger" id="admin-message" role="alert" aria-live="polite">
			<div class="admin-message-text">
				<strong><span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span> Something broken? It looks like <a href="/Admin/DBMaintenance" class="alert-link">database maintenance</a> needs to be completed</strong>
			</div>
		</div>
	</div>
{/if}