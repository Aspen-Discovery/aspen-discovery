{strip}
	{* More Like This *}
	{if $showMoreLikeThisInExplore}
		{include file="GroupedWork/exploreMoreLikeThis.tpl"}
	{/if}
{if !empty($exploreMoreSections)}
<div id="explore-more-menu" class="sidebar-links">
	<div class="panel-group{* accordion*}" id="explore-more-accordion">
		{foreach from=$exploreMoreSettings item=exploreMoreSection}
			{assign var="sectionId" value=$exploreMoreSection->section}
			{if ($exploreMoreSections[$sectionId])}
				{assign var="section" value=$exploreMoreSections[$sectionId]}


		<div id="exploreMoreSideBar-{$sectionId}Panel" class="panel{if $exploreMoreSection->openByDefault} active{/if}">

			{* Clickable header for my account section *}
			<a data-toggle="collapse"{* data-parent="#explore-more-accordion"*} href="#exploreMoreSideBar-{$sectionId}PanelBody">
				<div class="panel-title exploreMoreTitle">
					{if empty($exploreMoreSection->displayName)}
						{$archiveSections[$sectionId]}
					{else}
						{$exploreMoreSection->displayName}
					{/if}
				</div>
			</a>

			<div id="exploreMoreSideBar-{$sectionId}PanelBody" class="panel-collapse collapse{if $exploreMoreSection->openByDefault} in{/if}">
				<div class="panel-body">

		{if $section.format == 'scroller'}
			{* JCarousel with related titles *}
			<div class="jcarousel-wrapper">
				<a href="#" class="jcarousel-control-prev"{* data-target="-=1"*}><i class="glyphicon glyphicon-chevron-left"></i></a>
				<a href="#" class="jcarousel-control-next"{* data-target="+=1"*}><i class="glyphicon glyphicon-chevron-right"></i></a>

				<div class="relatedTitlesContainer jcarousel"> {* relatedTitlesContainer used in initCarousels *}
					<ul>
						{foreach from=$section.values item=title}
							<li class="relatedTitle">
								<a href="{$title.link}">
									<figure class="thumbnail">
										<img src="{$title.image}" alt="{$title.label|removeTrailingPunctuation|truncate:40:"..."}">
										<figcaption>{$title.label|removeTrailingPunctuation|truncate:40:"..."}</figcaption>
									</figure>
								</a>
							</li>
						{/foreach}
					</ul>
				</div>
			</div>

		{elseif $section.format == 'subsections'}
			{foreach from=$section.values item=section}
				<div class="section">

					<div class="row">
						<div class="subsectionTitle col-xs-5">{$section.title}</div>
						<div class="subsection col-xs-7">
							<a href="{$section.link}"><img src="{$section.image}" alt="{$section.description}" class="img-responsive img-thumbnail"></a>
						</div>
					</div>
				</div>
			{/foreach}
		{elseif $section.format == 'scrollerWithLink'}
			{* Related Titles Widget *}
			<div class="jcarousel-wrapper">
				<a href="#" class="jcarousel-control-prev"{* data-target="-=1"*}><i class="glyphicon glyphicon-chevron-left"></i></a>
				<a href="#" class="jcarousel-control-next"{* data-target="+=1"*}><i class="glyphicon glyphicon-chevron-right"></i></a>

				<div class="relatedTitlesContainer jcarousel"> {* relatedTitlesContainer used in initCarousels *}
					<ul>
						{foreach from=$section.values item=title}
							<li class="relatedTitle">
								<a href="{$title.link}">
									<figure class="thumbnail">
										<img src="{$title.image}" alt="{$title.label|removeTrailingPunctuation|truncate:40:"..."}">
										<figcaption>{$title.label|removeTrailingPunctuation|truncate:40:"..."}</figcaption>
									</figure>
								</a>
							</li>
						{/foreach}
					</ul>
				</div>
			</div>
			<a class="explore-more-scroller-link" href="{$section.link}" {if $section.openInNewWindow}target="_blank"{/if}>All Results {if $section.numFound}({$section.numFound}){/if}</a>

		{elseif $section.format == 'tableOfContents'}
			<ul>
				{foreach from=$section.values item=value}
					<li>
						<a href="#" onclick="return VuFind.Archive.handleBookClick('{$bookPid}', '{$value.pid}', VuFind.Archive.activeBookViewer);">
							{$value.label}
						</a>
					</li>
				{/foreach}
			</ul>
		{elseif $section.format == 'textOnlyList'}
			<ul>
			{foreach from=$section.values item=value}
				<li>
					<a href="{$value.link}">
						{$value.label}
					</a>
					{if $value.linkingReason}
						&nbsp;<img src="/images/silk/help.png" title="{$value.linkingReason|escape}">
					{/if}
				</li>
			{/foreach}
			</ul>

		{else} {* list *}
			{* Simple display with one thumbnail per item *}
			{foreach from=$section.values item=value}
				<div class="section">
					<a href="{$value.link}">
						{if $value.image}
							<figure style="text-align: center">
								<img src="{$value.image}" alt="{$value.label}" class="img-responsive img-thumbnail">
								{if $section.showTitles}
									<figcaption>
										{$value.label}
									</figcaption>
								{/if}
							</figure>
						{else}
							{$value.label}
						{/if}
					</a>
					{if $value.linkingReason}
						&nbsp;<img src="/images/silk/help.png" title="{$value.linkingReason|escape}">
					{/if}
				</div>
			{/foreach}
		{/if}

				</div>
			</div>
		</div>
			{/if}
	{/foreach}

	</div>
</div>
{/if}


	{* Related Articles Widget *}
	{if !empty($relatedArticles)}
		<div class="sectionHeader">Articles and More</div>
		<div class="section">
			{foreach from=$relatedArticles item=section}
			<div class="row">
				<a href="{$section.link}">
					<div class="subsection col-xs-5">
						<img src="{$section.image}" alt="{$section.description}" class="img-responsive img-thumbnail">
					</div>
					<div class="subsectionTitle col-xs-7">{$section.title}</div>
				</a>
			</div>
			{/foreach}
		</div>
	{/if}

	{* Sections for Related Content From Novelist  *}
	{foreach from=$exploreMoreInfo item=exploreMoreOption}
		<div class="sectionHeader"{if $exploreMoreOption.hideByDefault} style="display: none;"{/if}>{$exploreMoreOption.label}</div>
		<div class="{*col-sm-12 *}jcarousel-wrapper"{if $exploreMoreOption.hideByDefault} style="display: none;"{/if}>
			<a href="#" class="jcarousel-control-prev"{* data-target="-=1"*}><i class="glyphicon glyphicon-chevron-left"></i></a>
			<a href="#" class="jcarousel-control-next"{* data-target="+=1"*}><i class="glyphicon glyphicon-chevron-right"></i></a>
			{$exploreMoreOption.body}
		</div>
	{/foreach}
{/strip}
{* Initialize accordion heading styling (Required due to being loaded via AJAX) *}

<script type="application/javascript">
	{if empty($exploreMoreSections) && empty($relatedArticles)}
		$('#sidebar-menu-option-explore-more,#explore-more-header,#explore-more-body').fadeOut().empty().remove();
		{if $displaySidebarMenu}
			VuFind.Menu.collapseSideBar();
		{/if}
	{else}
	{literal}
	$('#explore-more-menu .panel')
			.on('show.bs.collapse', function () {
				$(this).addClass('active')
			})
			.on('hide.bs.collapse', function () {
				$(this).removeClass('active');
			})
			.one('shown.bs.collapse', function () {
				VuFind.initCarousels( $(this).children('.panel-collapse.in').find('.jcarousel') );
			});
	{/literal}
	{/if}
</script>
