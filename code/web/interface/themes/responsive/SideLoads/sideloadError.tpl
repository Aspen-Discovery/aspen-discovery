{strip}
	<div id="main-content">
		<div class="btn-group">
			<a class="btn btn-sm btn-default" href="/SideLoads/SideLoads?objectAction=edit&amp;id={$id}">{translate text="Edit Profile" isAdminFacing=true}</a>
			{foreach from=$additionalObjectActions item=action}
				{if $smarty.server.REQUEST_URI != $action.url}
					<a class="btn btn-default btn-sm" href='{$action.url}'>{$action.text}</a>
				{/if}
			{/foreach}
			<a class="btn btn-sm btn-default" href='/SideLoads/SideLoads?objectAction=list'>{translate text="Return to List" isAdminFacing=true}</a>
		</div>
		<h1>{$IndexProfileName}</h1>
		<div class="alert alert-warning">{$error}</div>
	</div>
{/strip}