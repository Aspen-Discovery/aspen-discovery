{strip}
	<div class="col-xs-12">
		<h1>{translate text="Research & Learn" isPublicFacing=true}</h1>
		<h2>{translate text="Featured Resources" isPublicFacing=true}</h2>
		<div class="row">
			{foreach from=$featuredResources item=resource}
				<div class="col-xs-4 col-md-3 col-lg-2 featuredResource">
					<a href="/WebBuilder/WebResource?id={$resource->id}">
					{if !empty($resource->logo)}
						<img src='/files/thumbnail/{$resource->logo}' alt="{$resource->name}" class="img-responsive img-thumbnail">
					{else}
						{$resource->name}
					{/if}
					</a>
				</div>
			{/foreach}
		</div>

		<h2>{translate text="Resources by Category" isPublicFacing=true}</h2>
		{foreach from=$resourcesByCategory key=category item=resources}
			<div class="panel resourceCategory" id="{$category|regex_replace:'/\W/':''|escape:css}Panel">
				<a data-toggle="collapse" href="#{$category|regex_replace:'/\W/':''|escape:css}PanelBody">
					<div class="panel-heading">
						<div class="panel-title">
							{$category|translate}
						</div>
					</div>
				</a>
				<div id="{$category|regex_replace:'/\W/':''|escape:css}PanelBody" class="panel-collapse collapse">
					<div class="panel-body">
						{foreach from=$resources item=resource}
							<div class="row webResourceRow">
								{if !empty($resource->logo)}
								<div class="coversColumn col-xs-3 col-sm-3 col-md-3 col-lg-2 text-center" aria-hidden="true" role="presentation">
										<a href="/WebBuilder/WebResource?id={$resource->id}" tabindex="-1">
											<img src='/files/thumbnail/{$resource->logo}' alt="{$resource->name}" class="img-responsive img-thumbnail">
										</a>

								</div>
								{/if}
								<div class="{if !empty($resource->logo)}col-xs-9 col-sm-9 col-md-9 col-lg-10{else}col-xs-12 col-sm-12 col-md-12 col-lg-12{/if}">
									<div>
										<a href="/WebBuilder/WebResource?id={$resource->id}" class="result-title">
											{$resource->name}
										</a>
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