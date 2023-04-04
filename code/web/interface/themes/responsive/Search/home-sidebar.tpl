{strip}
{* Search box *}
	<div class="row">
		{if !empty($userPermissions)}
			<div style="margin-bottom: 1em">
				<a href="/Admin/Home" class="btn btn-primary btn-block">
					<i class="fas fa-tools fa-fw"></i> {translate text="Aspen Administration" isAdminFacing=true}
				</a>
			</div>
		{/if}
	</div>

	{if !empty($loggedIn)}
		{* Account Menu *}
		{include file="MyAccount/account-sidebar.tpl"}
	{/if}

{/strip}