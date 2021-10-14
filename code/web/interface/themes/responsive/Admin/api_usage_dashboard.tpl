{strip}
	<div id="main-content" class="col-sm-12">
		<h1>{translate text="Aspen Discovery API Usage Dashboard" isAdminFacing=true}</h1>
		{include file="Admin/selectInterfaceForm.tpl"}

		{foreach from=$statsByModule key=moduleName item=moduleStats}
			<h2>{$moduleName}</h2> {* No translation needed *}
			<div class="row">
				{foreach from=$moduleStats key=method item=methodStats}
					<div class="dashboardCategory col-sm-6">
						<div class="row">
							<div class="col-sm-10 col-sm-offset-1">
								<h3 class="dashboardCategoryLabel">{$method}</h3> {* No translation needed *}
							</div>
						</div>
						<div class="row">
							<div class="col-tn-6">
								<div class="dashboardLabel">{translate text="This Month" isAdminFacing=true}</div>
								<div class="dashboardValue">{$methodStats.usageThisMonth|number_format}</div>
							</div>
							<div class="col-tn-6">
								<div class="dashboardLabel">{translate text="Last Month" isAdminFacing=true}</div>
								<div class="dashboardValue">{$methodStats.usageLastMonth|number_format}</div>
							</div>
							<div class="col-tn-6">
								<div class="dashboardLabel">{translate text="This Year" isAdminFacing=true}</div>
								<div class="dashboardValue">{$methodStats.usageThisYear|number_format}</div>
							</div>
							<div class="col-tn-6">
								<div class="dashboardLabel">{translate text="All Time" isAdminFacing=true}</div>
								<div class="dashboardValue">{$methodStats.usageAllTime|number_format}</div>
							</div>
						</div>
					</div>
				{/foreach}
			</div>
		{/foreach}
	</div>
{/strip}