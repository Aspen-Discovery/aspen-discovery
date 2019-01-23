{strip}
	<div class="row" id="vertical-menu-bar-container">
		{if !$sideBarOnRight}
			{include file="vertical-sidebar-menu.tpl"}
		{/if}

		<div class="col-xs-12{if $displaySidebarMenu} col-sm-10 col-md-10 col-lg-10{/if}" id="sidebar-content">
			{* Full Column width *}
			{include file="$sidebar"}
		</div>

		{if $sideBarOnRight}
			{include file="vertical-sidebar-menu.tpl"}
		{/if}

	</div>
{/strip}