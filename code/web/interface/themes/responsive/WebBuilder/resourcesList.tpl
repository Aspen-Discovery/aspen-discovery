{strip}
	<div class="col-xs-12">
		<h1>{translate text="Research & Learn"}</h1>
		<h2>{translate text="Featured Resources"}</h2>
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

		<h2>{translate text="Resources by Category"}</h2>
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
							<div class="row">
								<div class="coversColumn col-xs-3 col-sm-3 col-md-3 col-lg-2 text-center">
									{if !empty($resource->logo)}
										<a href="/WebBuilder/WebResource?id={$resource->id}">
											<img src='/files/thumbnail/{$resource->logo}' alt="{$resource->name}" class="img-responsive img-thumbnail">
										</a>
									{/if}
								</div>
								<div class="col-xs-9 col-sm-9 col-md-9 col-lg-10">
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