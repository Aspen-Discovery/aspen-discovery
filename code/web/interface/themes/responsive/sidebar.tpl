{strip}
	<div class="row" id="vertical-menu-bar-container">
		<h2 class="hiddenTitle" id="sidebarNav">{translate text="Main Navigation" isPublicFacing=true}</h2>
{*		{include file="vertical-sidebar-menu.tpl"}*}

		<div class="col-xs-12" id="sidebar-content">
			{* Full Column width *}
			{include file="$sidebar"}
		</div>
	</div>
{/strip}