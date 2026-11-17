{strip}
	<div class="row">
		<div class="col-xs-12">
			<h1>{translate text="Resources A to Z" isPublicFacing=true inAttribute=true}</h1>
		</div>
		<div class="col-xs-12">
			{$description}
		</div>
		<div id="filterAtoZ-navbar" class="navbar-horizontal">
			<ul id="filterAtoZ-list" class="navbar-horizontal-items" >
				{foreach from=$filterArray item=filter}
					<li id="filterAtoZ-listItem" class="navbar-horizontal-item">
					{if in_array($filter, $validLetters)}
							{if $filter == 'num'}
								<a  class="btn btn-primary btn-sm" href="/WebBuilder/ResourcesAtoZ?startsWith={$filter}">#</a>
							{else}
								<a  class="btn btn-primary btn-sm" href="/WebBuilder/ResourcesAtoZ?startsWith={$filter}">{$filter}</a>
							{/if}
					{else}
						{if $filter == 'num'}
							<a  class="btn btn-primary btn-sm" disabled="true">#</a>
						{else}
							<a  class="btn btn-primary btn-sm" disabled="true">{$filter}</a>
						{/if}
					{/if}
					</li>
				{/foreach}
				<li class=""  style="margin-left: auto;">
					<a  class="btn btn-primary btn-sm" href="/WebBuilder/ResourcesAtoZ?startsWith=All">View All</a>
				</li>
			</ul>
		</div>
	</div>
	{foreach from=$webResources item=curResource}
		{assign var="curResourceTitle" value=$curResource.title|removeTrailingPunctuation}
		{if empty($curResourceTitle)}
			{capture assign="curResourceTitle"}{translate text='Title not available' isPublicFacing=true}{/capture}
		{/if}
		<div id="webPageResult" class="resultsList row">
			<div class="coversColumn col-xs-3 col-sm-3 col-md-3 col-lg-2 text-center" aria-hidden="true" role="presentation">
				{capture assign="curResourceThumbnailContent"}<img src="{$curResource.bookCoverUrl}" class="listResultImage img-thumbnail {$coverStyle}" alt="{$curResourceTitle|highlight|truncate:180:"..."}">{/capture}
				{include file='WebBuilder/webResourceListLink.tpl' resourceId=$curResource.id resourceName=$curResourceTitle resourceUrl=$curResource.url openInNewTab=$curResource.openInNewTab linkClass='alignleft listResultImage' linkTabIndex='-1' linkContent=$curResourceThumbnailContent openWebResourcesDirectly=$openWebResourcesDirectly}
			</div>
			<div class="col-xs-9 col-sm-9 col-md-9 col-lg-10">
				<div class="col-xs-12">
					<span class="result-index"></span>&nbsp;
					{capture assign="curResourceTitleContent"}{$curResourceTitle|highlight|truncate:180:"..."}{/capture}
					{include file='WebBuilder/webResourceListLink.tpl' resourceId=$curResource.id resourceName=$curResourceTitle resourceUrl=$curResource.url openInNewTab=$curResource.openInNewTab linkClass='result-title notranslate' linkContent=$curResourceTitleContent openWebResourcesDirectly=$openWebResourcesDirectly}
				</div>
			</div>

			{* Description Section *}
			{if !empty($curResource.description)}
				<div class="result-value col-tn-9 col-xs-9">
					<div class="col-xs-12">
						{$curResource.description|highlight|truncate_html:450:"..."}
					</div>
				</div>
			{/if}
		</div>
	{/foreach}
{/strip}
