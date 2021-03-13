{strip}
	<div id="main-content" class="col-sm-12">
		<h1>{translate text="Aspen Discovery API Usage Dashboard"}</h1>
		{include file="Admin/selectInterfaceForm.tpl"}

		{foreach from=$statsByModule key=moduleName item=moduleStats}
			<h2>{$moduleName}</h2>
			<div class="row">
				{foreach from=$moduleStats key=method item=methodStats}
					<div class="dashboardCategory col-sm-6">
						<div class="row">
							<div class="col-sm-10 col-sm-offset-1">
								<h3 class="dashboardCategoryLabel">{$method}</h3>
							</div>
						</div>
						<div class="row">
							<div class="col-tn-6">
								<div class="dashboardLabel">{translate text="This Month"}</div>
								<div class="dashboardValue">{$methodStats.usageThisMonth|number_format}</div>
							</div>
							<div class="col-tn-6">
								<div class="dashboardLabel">{translate text="Last Month"}</div>
								<div class="dashboardValue">{$methodStats.usageLastMonth|number_format}</div>
							</div>
							<div class="col-tn-6">
								<div class="dashboardLabel">{translate text="This Year"}</div>
								<div class="dashboardValue">{$methodStats.usageThisYear|number_format}</div>
							</div>
							<div class="col-tn-6">
								<div class="dashboardLabel">{translate text="All Time"}</div>
								<div class="dashboardValue">{$methodStats.usageAllTime|number_format}</div>
							</div>
						</div>
					</div>
				{/foreach}
			</div>
		{/foreach}
	</div>
{/strip}