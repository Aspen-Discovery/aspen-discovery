{strip}
	{* Added Breadcrumbs to appear above the format filter icons - JE 6/26/15 *}
	{if $showBreadcrumbs}
	<div class="row breadcrumbs">
		<div class="hidden-xs col-xs-12 col-sm-9">
			<ul class="breadcrumb small">
				<li><a href="{$homeBreadcrumbLink}" id="home-breadcrumb"><i class="fas fa-home fa"></i> {translate text=$homeLinkText}</a></li>
				{foreach from=$breadcrumbs item=breadcrumb}
					<li>
						<span class="divider">&raquo; </span>
						{if $breadcrumb->link}
							<a href="{$breadcrumb->link}">
						{/if}
						{if $breadcrumb->translate}
							{$breadcrumb->label|translate}
						{else}
							{$breadcrumb->label}
						{/if}
						{if $breadcrumb->link}
							</a>
						{/if}
					</li>
				{/foreach}
			</ul>
		</div>
	</div>
	{/if}
{/strip}