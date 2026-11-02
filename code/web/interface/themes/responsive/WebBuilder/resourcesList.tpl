{strip}
	<div class="col-xs-12">
		<h1>{translate text="Research & Learn" isPublicFacing=true}</h1>
		{if !empty($featuredResources)}
			<h2>{translate text="Featured Resources" isPublicFacing=true}</h2>
			<div class="row">
				{foreach from=$featuredResources item=resource}
					<div class="col-xs-4 col-md-3 col-lg-2 featuredResource">
						{capture assign="featuredResourceContent"}{if !empty($resource->logo)}<img src='/files/thumbnail/{$resource->logo}' alt="{$resource->name|escape}" class="img-responsive img-thumbnail">{else}{$resource->name|escape}{/if}{/capture}
						{include file='WebBuilder/webResourceListLink.tpl' resourceId=$resource->id resourceName=$resource->name resourceUrl=$resource->_urlForLibrary openInNewTab=$resource->openInNewTab linkContent=$featuredResourceContent openWebResourcesDirectly=$openWebResourcesDirectly}
					</div>
				{/foreach}
			</div>
		{/if}

		<div class="row">
			<div class="col-xs-4 col-md-3 col-lg-2" style="margin-top:20px;">
				<a href="/WebBuilder/ResourcesAtoZ" class="btn btn-primary" target="_blank" aria-label="{translate text="Resources A to Z" isPublicFacing=true inAttribute=true} ({translate text="opens in a new window" isPublicFacing=true inAttribute=true})"><i class="fas fa-external-link-alt" role="presentation"></i> {translate text="Resources A to Z" isPublicFacing=true}</a>
			</div>
		</div>

		<h2>{translate text="Resources by Category" isPublicFacing=true}</h2>
		{foreach from=$resourcesByCategory key=category item=resources}
			<div class="panel resourceCategory" id="{$category|regex_replace:'/\W/':''|escapeCSS}Panel">
				<a data-toggle="collapse" href="#{$category|regex_replace:'/\W/':''|escapeCSS}PanelBody">
					<div class="panel-heading">
						<div class="panel-title">
							{translate text=$category isPublicFacing=true}
						</div>
					</div>
				</a>
				<div id="{$category|regex_replace:'/\W/':''|escapeCSS}PanelBody" class="panel-collapse collapse">
					<div class="panel-body">
						{foreach from=$resources item=resource}
							<div class="row webResourceRow">
								{if !empty($resource->logo)}
								<div class="coversColumn col-xs-3 col-sm-3 col-md-3 col-lg-2 text-center" aria-hidden="true" role="presentation">
										{capture assign="resourceThumbnailContent"}<img src='/files/thumbnail/{$resource->logo}' alt="{$resource->name|escape}" class="img-responsive img-thumbnail">{/capture}
										{include file='WebBuilder/webResourceListLink.tpl' resourceId=$resource->id resourceName=$resource->name resourceUrl=$resource->_urlForLibrary openInNewTab=$resource->openInNewTab linkTabIndex='-1' linkContent=$resourceThumbnailContent openWebResourcesDirectly=$openWebResourcesDirectly}

								</div>
								{/if}
								<div class="{if !empty($resource->logo)}col-xs-9 col-sm-9 col-md-9 col-lg-10{else}col-xs-12 col-sm-12 col-md-12 col-lg-12{/if}">
									<div>
										{capture assign="resourceTitleContent"}{$resource->name|escape}{/capture}
										{include file='WebBuilder/webResourceListLink.tpl' resourceId=$resource->id resourceName=$resource->name resourceUrl=$resource->_urlForLibrary openInNewTab=$resource->openInNewTab linkClass='result-title' linkContent=$resourceTitleContent openWebResourcesDirectly=$openWebResourcesDirectly}
									</div>
									<div>
										{$resource->teaser}
									</div>
								</div>
							</div>
						{/foreach}
					</div>
				</div>
			</div>

		{/foreach}
	</div>
{/strip}
