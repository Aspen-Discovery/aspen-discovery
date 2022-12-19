{strip}
	<div id="main-content" class="col-sm-12">
		<h1>{translate text="Aspen Site Stats Dashboard" isAdminFacing=true}</h1>
		{include file="Greenhouse/selectSiteForm.tpl"}

		<h2>{translate text="General Usage" isAdminFacing=true}</h2>
		<div class="row">
			<div class="dashboardCategory col-sm-6">
				<div class="row">
					<div class="col-sm-10 col-sm-offset-1">
						<h3 class="dashboardCategoryLabel">{translate text="Min Data Disk Space" isAdminFacing=true}</h3>
					</div>
				</div>
				<div class="row">
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="Today" isAdminFacing=true}</div>
						<div class="dashboardValue">{$siteStatsToday.minDataDiskSpace|number_format:2} GB</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Month" isAdminFacing=true}</div>
						<div class="dashboardValue">{$siteStatsThisMonth.minDataDiskSpace|number_format:2} GB</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="Last Month" isAdminFacing=true}</div>
						<div class="dashboardValue">{$siteStatsLastMonth.minDataDiskSpace|number_format:2} GB</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Year" isAdminFacing=true}</div>
						<div class="dashboardValue">{$siteStatsThisYear.minDataDiskSpace|number_format:2} GB</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="All Time" isAdminFacing=true}</div>
						<div class="dashboardValue">{$siteStatsAllTime.minDataDiskSpace|number_format:2} GB</div>
					</div>
				</div>
			</div>

			<div class="dashboardCategory col-sm-6">
				<div class="row">
					<div class="col-sm-10 col-sm-offset-1">
						<h3 class="dashboardCategoryLabel">{translate text="Min Usr Disk Space" isAdminFacing=true}</h3>
					</div>
				</div>
				<div class="row">
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="Today" isAdminFacing=true}</div>
						<div class="dashboardValue">{$siteStatsToday.minUsrDiskSpace|number_format:2} GB</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Month" isAdminFacing=true}</div>
						<div class="dashboardValue">{$siteStatsThisMonth.minUsrDiskSpace|number_format:2} GB</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="Last Month" isAdminFacing=true}</div>
						<div class="dashboardValue">{$siteStatsLastMonth.minUsrDiskSpace|number_format:2} GB</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Year" isAdminFacing=true}</div>
						<div class="dashboardValue">{$siteStatsThisYear.minUsrDiskSpace|number_format:2} GB</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="All Time" isAdminFacing=true}</div>
						<div class="dashboardValue">{$siteStatsAllTime.minUsrDiskSpace|number_format:2} GB</div>
					</div>
				</div>
			</div>

			<div class="dashboardCategory col-sm-6">
				<div class="row">
					<div class="col-sm-10 col-sm-offset-1">
						<h3 class="dashboardCategoryLabel">{translate text="Min Avilable Memory" isAdminFacing=true}</h3>
					</div>
				</div>
				<div class="row">
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="Today" isAdminFacing=true}</div>
						<div class="dashboardValue">{$siteStatsToday.minAvailableMemory|number_format:2} GB</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Month" isAdminFacing=true}</div>
						<div class="dashboardValue">{$siteStatsThisMonth.minAvailableMemory|number_format:2} GB</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="Last Month" isAdminFacing=true}</div>
						<div class="dashboardValue">{$siteStatsLastMonth.minAvailableMemory|number_format:2} GB</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Year" isAdminFacing=true}</div>
						<div class="dashboardValue">{$siteStatsThisYear.minAvailableMemory|number_format:2} GB</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="All Time" isAdminFacing=true}</div>
						<div class="dashboardValue">{$siteStatsAllTime.minAvailableMemory|number_format:2} GB</div>
					</div>
				</div>
			</div>

			<div class="dashboardCategory col-sm-6">
				<div class="row">
					<div class="col-sm-10 col-sm-offset-1">
						<h3 class="dashboardCategoryLabel">{translate text="Max Avilable Memory" isAdminFacing=true}</h3>
					</div>
				</div>
				<div class="row">
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="Today" isAdminFacing=true}</div>
						<div class="dashboardValue">{$siteStatsToday.maxAvailableMemory|number_format:2} GB</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Month" isAdminFacing=true}</div>
						<div class="dashboardValue">{$siteStatsThisMonth.maxAvailableMemory|number_format:2} GB</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="Last Month" isAdminFacing=true}</div>
						<div class="dashboardValue">{$siteStatsLastMonth.maxAvailableMemory|number_format:2} GB</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Year" isAdminFacing=true}</div>
						<div class="dashboardValue">{$siteStatsThisYear.maxAvailableMemory|number_format:2} GB</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="All Time" isAdminFacing=true}</div>
						<div class="dashboardValue">{$siteStatsAllTime.maxAvailableMemory|number_format:2} GB</div>
					</div>
				</div>
			</div>

			<div class="dashboardCategory col-sm-6">
				<div class="row">
					<div class="col-sm-10 col-sm-offset-1">
						<h3 class="dashboardCategoryLabel">{translate text="Min Load Per CPU" isAdminFacing=true}</h3>
					</div>
				</div>
				<div class="row">
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="Today" isAdminFacing=true}</div>
						<div class="dashboardValue">{$siteStatsToday.minLoadPerCPU|number_format:2}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Month" isAdminFacing=true}</div>
						<div class="dashboardValue">{$siteStatsThisMonth.minLoadPerCPU|number_format:2}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="Last Month" isAdminFacing=true}</div>
						<div class="dashboardValue">{$siteStatsLastMonth.minLoadPerCPU|number_format:2}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Year" isAdminFacing=true}</div>
						<div class="dashboardValue">{$siteStatsThisYear.minLoadPerCPU|number_format:2}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="All Time" isAdminFacing=true}</div>
						<div class="dashboardValue">{$siteStatsAllTime.minLoadPerCPU|number_format:2}</div>
					</div>
				</div>
			</div>

			<div class="dashboardCategory col-sm-6">
				<div class="row">
					<div class="col-sm-10 col-sm-offset-1">
						<h3 class="dashboardCategoryLabel">{translate text="Max Load Per CPU" isAdminFacing=true}</h3>
					</div>
				</div>
				<div class="row">
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="Today" isAdminFacing=true}</div>
						<div class="dashboardValue">{$siteStatsToday.maxLoadPerCPU|number_format:2}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Month" isAdminFacing=true}</div>
						<div class="dashboardValue">{$siteStatsThisMonth.maxLoadPerCPU|number_format:2}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="Last Month" isAdminFacing=true}</div>
						<div class="dashboardValue">{$siteStatsLastMonth.maxLoadPerCPU|number_format:2}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Year" isAdminFacing=true}</div>
						<div class="dashboardValue">{$siteStatsThisYear.maxLoadPerCPU|number_format:2}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="All Time" isAdminFacing=true}</div>
						<div class="dashboardValue">{$siteStatsAllTime.maxLoadPerCPU|number_format:2}</div>
					</div>
				</div>
			</div>

			<div class="dashboardCategory col-sm-6">
				<div class="row">
					<div class="col-sm-10 col-sm-offset-1">
						<h3 class="dashboardCategoryLabel">{translate text="Max Wait Time" isAdminFacing=true}</h3>
					</div>
				</div>
				<div class="row">
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="Today" isAdminFacing=true}</div>
						<div class="dashboardValue">{$siteStatsToday.maxWaitTime|number_format:2}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Month" isAdminFacing=true}</div>
						<div class="dashboardValue">{$siteStatsThisMonth.maxWaitTime|number_format:2}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="Last Month" isAdminFacing=true}</div>
						<div class="dashboardValue">{$siteStatsLastMonth.maxWaitTime|number_format:2}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Year" isAdminFacing=true}</div>
						<div class="dashboardValue">{$siteStatsThisYear.maxWaitTime|number_format:2}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="All Time" isAdminFacing=true}</div>
						<div class="dashboardValue">{$siteStatsAllTime.maxWaitTime|number_format:2}</div>
					</div>
				</div>
			</div>
		</div>
	</div>
{/strip}