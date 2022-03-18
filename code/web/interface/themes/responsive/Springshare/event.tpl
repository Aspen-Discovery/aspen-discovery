<div class="col-xs-12">
	<h1>{$recordDriver->getTitle()}</h1>
	<div class="row">
		<div class="col-sm-2">
			<a href="{$recordDriver->getLinkUrl()}"><img class="img-responsive img-thumbnail" src="{$recordDriver->getEventCoverUrl()}" alt="{$recordDriver->getTitle()|escape}"></a>
		</div>
		<div class="col-sm-10 col-md-7">
			<p>{$recordDriver->getDescription()}</p>

			<a class="btn btn-primary" href="{$recordDriver->getLinkUrl()}">{translate text="View on LibCal" isPublicFacing=true}</a>
		</div>
		<div class="col-sm-12 col-md-3">
			{if !empty($recordDriver->getAudiences())}
				<div class="panel active">
					<div class="panel-heading">
						{translate text="Audience" isPublicFacing=true}
					</div>

					<div class="panel-body">
						{foreach from=$recordDriver->getAudiences() item=audience}
							<div class="col-xs-12">
								<a href='/Events/Results?filter[]=age_group_facet%3A"{$audience|escape:'url'}"'>{$audience}</a>
							</div>
						{/foreach}
					</div>
				</div>
			{/if}
			{if !empty($recordDriver->getCategories())}
				<div class="panel active">
					<div class="panel-heading">
						{translate text="Category" isPublicFacing=true}
					</div>
					<div class="panel-body">
						{foreach from=$recordDriver->getCategories() item=category}
							<div class="col-xs-12">
								<a href='/Events/Results?filter[]=program_type_facet%3A"{$category|escape:'url'}"'>{$category}</a>
							</div>
						{/foreach}
					</div>
				</div>
			{/if}
		</div>
	</div>

</div>