{strip}
{* Search box *}
	<div class="row">
		{if !empty($userPermissions)}
			<a href="/Admin/Home">
				<div class="sidebar-button custom-sidebar-button">
					{translate text="Aspen Administration" isAdminFacing=true}
				</div>
			</a>
		{/if}
	</div>

	{if $loggedIn}
		{* Account Menu *}
		{include file="MyAccount/account-sidebar.tpl"}
	{/if}

{/strip}