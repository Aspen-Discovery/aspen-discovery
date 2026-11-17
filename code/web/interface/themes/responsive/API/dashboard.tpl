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
								<h3 class="dashboardCategoryLabel">{$method}{' '} {* No translation needed *}
									<a href="/API/UsageGraphs?stat={$method}&instance={$selectedInstance}" title="{translate text="{$moduleName}: {$method} Graph" inAttribute="true" isAdminFacing=true}">
										<i class="fas fa-chart-line"></i>
									</a>
								</h3>
							</div>
						</div>
						{foreach from=$methodStats key=apiClient item=clientStats}
							<div class="row">
								<div class="col-sm-10 col-sm-offset-1">
									<div class="dashboardCategoryLabel">{if $apiClient == ''}{translate text="Not recorded" isAdminFacing=true}{else}{$apiClient|escape}{/if}</div>
								</div>
							</div>
							<div class="row">
								<div class="col-tn-6">
									<div class="dashboardLabel">{translate text="This Month" isAdminFacing=true}</div>
									<div class="dashboardValue">{$clientStats.usageThisMonth|number_format}</div>
								</div>
								<div class="col-tn-6">
									<div class="dashboardLabel">{translate text="Last Month" isAdminFacing=true}</div>
									<div class="dashboardValue">{$clientStats.usageLastMonth|number_format}</div>
								</div>
								<div class="col-tn-6">
									<div class="dashboardLabel">{translate text="This Year" isAdminFacing=true}</div>
									<div class="dashboardValue">{$clientStats.usageThisYear|number_format}</div>
								</div>
								<div class="col-tn-6">
									<div class="dashboardLabel">{translate text="All Time" isAdminFacing=true}</div>
									<div class="dashboardValue">{$clientStats.usageAllTime|number_format}</div>
								</div>
							</div>
						{/foreach}
					</div>
				{/foreach}
			</div>
		{/foreach}
	</div>
{/strip}