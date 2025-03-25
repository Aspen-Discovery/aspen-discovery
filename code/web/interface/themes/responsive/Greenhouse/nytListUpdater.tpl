{strip}
	<div id="main-content" class="col-md-12">
		<h1>{translate text="New York Times List Updater" isAdminFacing=true}</h1>
		<div class="alert alert-info">
			{translate text="This tool allows you to run the New York Times lists updater immediately. Use this when you need to update NYT lists without waiting for the scheduled cron job." isAdminFacing=true}
		</div>

		{* Display status of any currently running update *}
		<div id="nytUpdateStatus"></div>

		{if !$hasSettings}
			<div class="alert alert-warning">
				{translate text="The New York Times API is not configured. Please configure it in the Admin settings first." isAdminFacing=true}
			</div>
		{else}
			<div class="row">
				<div class="col-xs-12 col-sm-6">
					<div class="panel panel-default">
						<div class="panel-heading">
							<h2 class="panel-title">{translate text="Settings" isAdminFacing=true}</h2>
						</div>
						<div class="panel-body">
							<div class="form-group">
								<label>{translate text="API Key (Truncated)" isAdminFacing=true}</label>
								<div class="form-control-static">{$apiKey}</div>
								<p class="help-block">
									<small>{translate text="To modify this key, please visit the full settings page." isAdminFacing=true}</small>
								</p>
							</div>

							<div class="form-group">
								<div class="checkbox">
									<label>
										<input type="checkbox" id="forceFullUpdate" onchange="AspenDiscovery.Greenhouse.toggleNYTSetting('forceFullUpdate', this);" {if $forceFullUpdate}checked{/if}>
										{translate text="Force Full Update" isAdminFacing=true}
									</label>
									<p class="help-block">
										<small>{translate text="When enabled, all lists will be completely rebuilt regardless of the last modified date." isAdminFacing=true}</small>
									</p>
								</div>
							</div>

							<div class="form-group">
								<div class="checkbox">
									<label>
										<input type="checkbox" id="enableExtensiveLogging" onchange="AspenDiscovery.Greenhouse.toggleNYTSetting('enableExtensiveLogging', this);" {if $enableExtensiveLogging}checked{/if}>
										{translate text="Enable Extensive Logging" isAdminFacing=true}
									</label>
									<p class="help-block">
										<small>{translate text="When enabled, more detailed logs will be generated during the update process." isAdminFacing=true}</small>
									</p>
								</div>
							</div>

							<div class="form-group">
								<p>
									<a href="/Enrichment/NewYorkTimesSettings" class="btn btn-default" target="_blank">
										<i class="fas fa-cog"></i> {translate text="Advanced Settings" isAdminFacing=true}
									</a>
								</p>
							</div>
						</div>
					</div>
				</div>

				<div class="col-xs-12 col-sm-6">
					<div class="panel panel-default">
						<div class="panel-heading">
							<h2 class="panel-title">{translate text="Run Update" isAdminFacing=true}</h2>
						</div>
						<div class="panel-body">
							<p>{translate text="Click the button below to run the NYT lists updater now. This will execute the command on the server and may take several minutes to complete." isAdminFacing=true}</p>
							<button id="runNytUpdateBtn" onclick="return AspenDiscovery.Greenhouse.runNYTUpdate();" class="btn btn-primary"
									data-original-text="<i class='fas fa-sync'></i> {translate text="Run NYT Lists Update" isAdminFacing=true}"
									data-running-text="{translate text="Update Running..." isAdminFacing=true}">
								<i class="fas fa-sync"></i> {translate text="Run NYT Lists Update" isAdminFacing=true}
							</button>

							<div class="form-group" style="margin-top: 20px;">
								<a href="/UserLists/NYTUpdatesLog" class="btn btn-default" target="_blank">
									<i class="fas fa-history"></i> {translate text="View All Update Logs" isAdminFacing=true}
								</a>
							</div>
						</div>
					</div>
				</div>
			</div>
		{/if}
	</div>
{/strip}
