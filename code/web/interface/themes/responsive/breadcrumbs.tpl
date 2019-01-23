{strip}
	{* Added Breadcrumbs to appear above the format filter icons - JE 6/26/15 *}
	{if $showBreadcrumbs}
	<div class="row breadcrumbs">
		<div class="hidden-xs col-xs-12 col-sm-9">
			<ul class="breadcrumb small">
				<li><a href="{$homeBreadcrumbLink}" id="home-breadcrumb"><i class="icon-home"></i> {translate text=$homeLinkText}</a> <span class="divider">&raquo;</span></li>
				{include file="$module/breadcrumbs.tpl"}
			</ul>
		</div>
	</div>
	{/if}
{/strip}