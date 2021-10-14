<div class="col-xs-12">
	<h1>{$title}</h1>
    {if $loggedIn && (array_key_exists('Administer All Web Resources', $userPermissions) || array_key_exists('Administer Library Web Resources', $userPermissions))}
		<div class="row">
			<div class="col-xs-12">
				<a href="/WebBuilder/WebResources?id={$id}&objectAction=edit" class="btn btn-default btn-sm">{translate text=Edit isAdminFacing=true}</a>
			</div>
		</div>

	{/if}
	<div class="row">
		<div class="col-sm-2">
			<a href="{$webResource->url}" {if $webResource->openInNewTab}target="_blank"{/if}>
				<img class="img-responsive img-thumbnail" src="{$logo}" alt="{$title|escape}">
			</a>
		</div>
		<div class="col-sm-10 col-md-7">
			{$description}

			{if $webResource->requiresLibraryCard}
				<p><em>
					{translate text="This resource requires a library card to use it." isPublicFacing=true}
				</em></p>
			{/if}

			{if $webResource->inLibraryUseOnly}
				<p><em>
					{translate text="This resource requires you to be in the library to use it." isPublicFacing=true}
				</em></p>
			{/if}
			<a href="{$webResource->url}" class="btn btn-primary" {if $webResource->openInNewTab}target="_blank"{/if}>{translate text="Open Resource" isPublicFacing=true}</a>
		</div>
		<div class="col-sm-12 col-md-3">
			{if !empty($webResource->getAudiences())}
				<div class="panel active">
					<div class="panel-heading">
						{translate text="Audience" isPublicFacing=true}
					</div>

					<div class="panel-body">
						{foreach from=$webResource->getAudiences() item=audience}
							<div class="col-xs-12">
								<a href='/Websites/Results?filter[]=audience_facet%3A"{$audience->name}"'>{$audience->name}</a>
							</div>
						{/foreach}
					</div>
				</div>

			{/if}
			{if !empty($webResource->getCategories())}
				<div class="panel active">
					<div class="panel-heading">
						{translate text="Category" isPublicFacing=true}
					</div>
					<div class="panel-body">
						{foreach from=$webResource->getCategories() item=category}
							<div class="col-xs-12">
								<a href='/Websites/Results?filter[]=category_facet%3A"{$category->name}"'>{$category->name}</a>
							</div>
						{/foreach}
					</div>
				</div>
			{/if}
		</div>
	</div>

</div>