{strip}
	<div id="main-content" class="col-sm-12">
		<h3>{translate text="Cloud Library Dashboard"}</h3>
		<div class="dashboardCategory">
			<div class="row">
				<div class="col-sm-10 col-sm-offset-1">
					<h4 class="dashboardCategoryLabel">{translate text="Active Users"}</h4>
				</div>
			</div>
			<div class="row">
				<div class="col-tn-4">
					<div class="dashboardLabel">{translate text="This Month"}</div>
					<div class="dashboardValue">{$activeUsersThisMonth|number_format}</div>
				</div>
				<div class="col-tn-4">
					<div class="dashboardLabel">{translate text="This Year"}</div>
					<div class="dashboardValue">{$activeUsersThisYear|number_format}</div>
				</div>
				<div class="col-tn-4">
					<div class="dashboardLabel">{translate text="All Time"}</div>
					<div class="dashboardValue">{$activeUsersAllTime|number_format}</div>
				</div>
			</div>
		</div>

		<div class="dashboardCategory">
			<div class="row">
				<div class="col-sm-10 col-sm-offset-1">
					<h4 class="dashboardCategoryLabel">{translate text="Records With Usage"}</h4>
				</div>
			</div>
			<div class="row">
				<div class="col-tn-4">
					<div class="dashboardLabel">{translate text="This Month"}</div>
					<div class="dashboardValue">{$activeRecordsThisMonth|number_format}</div>
				</div>
				<div class="col-tn-4">
					<div class="dashboardLabel">{translate text="This Year"}</div>
					<div class="dashboardValue">{$activeRecordsThisYear|number_format}</div>
				</div>
				<div class="col-tn-4">
					<div class="dashboardLabel">{translate text="All Time"}</div>
					<div class="dashboardValue">{$activeRecordsAllTime|number_format}</div>
				</div>
			</div>
		</div>

		<div class="dashboardCategory">
			<div class="row">
				<div class="col-sm-10 col-sm-offset-1">
					<h4 class="dashboardCategoryLabel">{translate text="Loans"}</h4>
				</div>
			</div>
			<div class="row">
				<div class="col-tn-4">
					<div class="dashboardLabel">{translate text="This Month"}</div>
					<div class="dashboardValue">{$loansThisMonth|number_format}</div>
				</div>
				<div class="col-tn-4">
					<div class="dashboardLabel">{translate text="This Year"}</div>
					<div class="dashboardValue">{$loansThisYear|number_format}</div>
				</div>
				<div class="col-tn-4">
					<div class="dashboardLabel">{translate text="All Time"}</div>
					<div class="dashboardValue">{$loansAllTime|number_format}</div>
				</div>
			</div>
		</div>

		<div class="dashboardCategory">
			<div class="row">
				<div class="col-sm-10 col-sm-offset-1">
					<h4 class="dashboardCategoryLabel">{translate text="Holds"}</h4>
				</div>
			</div>
			<div class="row">
				<div class="col-tn-4">
					<div class="dashboardLabel">{translate text="This Month"}</div>
					<div class="dashboardValue">{$holdsThisMonth|number_format}</div>
				</div>
				<div class="col-tn-4">
					<div class="dashboardLabel">{translate text="This Year"}</div>
					<div class="dashboardValue">{$holdsThisYear|number_format}</div>
				</div>
				<div class="col-tn-4">
					<div class="dashboardLabel">{translate text="All Time"}</div>
					<div class="dashboardValue">{$holdsAllTime|number_format}</div>
				</div>
			</div>
		</div>

	</div>
{/strip}