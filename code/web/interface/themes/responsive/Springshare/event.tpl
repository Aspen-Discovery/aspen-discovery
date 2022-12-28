<div class="col-xs-12">
	<div class="row">
		<div class="col-sm-4">
			<div class="panel active">
				<div class="panel-body">
					<a href="{$recordDriver->getLinkUrl()}"><img class="img-responsive img-thumbnail {$coverStyle}" src="{$recordDriver->getEventCoverUrl()}" alt="{$recordDriver->getTitle()|escape}"></a>
				</div>
			</div>
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
		</div>
		<div class="col-sm-8">
				<h1>{$recordDriver->getTitle()}</h1>
				<ul>
					<li>Date: {$recordDriver->getStartDateString()|date_format:"%A %B %e, %Y"}</li>
					<li>Time: {$recordDriver->getStartDateString()|date_format:"%l:%M %p"} to {$recordDriver->getEndDateString()|date_format:"%l:%M %p %Z"}</li>
					<li>Branch: {$recordDriver->getBranch()}</li>
				</ul>
				<a class="btn btn-primary" href="{$recordDriver->getExternalUrl()}" target="_blank">
					{if $recordDriver->isRegistrationRequired()}
						{translate text="Register on LibCal" isPublicFacing=true}
					{else}
						{translate text="View on LibCal" isPublicFacing=true}
					{/if}
				</a>
		</div>
		<div class="col-sm-8">
				{$recordDriver->getDescription()}
		</div>
		{if !empty($loggedIn) && (in_array('Administer Springshare LibCal Settings', $userPermissions))}
			<div id="more-details-accordion" class="panel-group">
				<div class="panel" id="staffPanel">
					<a data-toggle="collapse" href="#staffPanelBody">
						<div class="panel-heading">
							<div class="panel-title">
								<h2>{translate text=Staff isPublicFacing=true}</h2>
							</div>
						</div>
					</a>
					<div id="staffPanelBody" class="panel-collapse collapse">
						<div class="panel-body">
							<h3>{translate text="Springshare LibCal Event API response" isPublicFacing=true}</h3>
							<pre>{$recordDriver->getStaffView()|print_r}</pre>
						</div>
{*							{if !empty($moreDetailsOption.onShow)}*}
{*								<script type="text/javascript">*}
{*									{literal}*}
{*									$('#{/literal}{$moreDetailsKey}Panel'){literal}.on('shown.bs.collapse', function () {*}
{*										{/literal}{$moreDetailsOption.onShow}{literal}*}
{*									});*}
{*									{/literal}*}
{*								</script>*}
{*							{/if}*}
					</div>
				</div>
			</div> {* End of tabs*}
		{/if}
	</div>
</div>