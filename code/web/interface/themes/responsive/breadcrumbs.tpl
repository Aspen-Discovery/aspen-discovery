{strip}
	{* Added Breadcrumbs to appear above the format filter icons - JE 6/26/15 *}
	{if $showBreadcrumbs}
	<div class="row breadcrumbs">
		<div class="col-xs-12">
			<ul class="breadcrumb small">
				{if !empty($homeLink)}
					<li>
						<a href="{$homeLink}" id="homeLink" class="menu-icon menu-bar-option" title="{translate text='Library Home Page' inAttribute=true}" aria-label="{translate text="Return to $homeLinkText" inAttribute=true}">
							<i class="fas fa-home"></i> {translate text="Home"}
						</a>
					</li>
				{/if}
				<li>
					{if !empty($homeLink)}
						<span class="divider">&raquo; </span>
					{/if}
					<a href="{if empty($homeLink)}/{else}/Search/Home{/if}" id="homeLink" class="menu-icon menu-bar-option" title="{translate text='Browse the catalog' inAttribute=true}" aria-label="{translate text='Browse the catalog' inAttribute=true}">
						<i class="fas {if empty($homeLink)}fa-home{else}fa-book-open{/if}"></i>{if !empty($homeLink)} {translate text='Browse'}{else} {translate text="Home"}{/if}
					</a>
				</li>
				{* <li><a href="{$homeBreadcrumbLink}" id="home-breadcrumb"><i class="fas fa-home fa"></i> {translate text=$homeLinkText}</a></li> *}
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