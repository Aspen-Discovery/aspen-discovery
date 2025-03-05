{strip}
	<div id="main-content" class="col-md-12">
		<h1>{translate text="New York Times List Updater" isAdminFacing=true}</h1>
		<div class="alert alert-info">
			{translate text="This tool allows you to run the New York Times lists updater immediately. Use this when you need to update NYT lists without waiting for the scheduled cron job." isAdminFacing=true}
		</div>

		{if !$hasSettings}
			<div class="alert alert-warning">
				{translate text="The New York Times API is not configured. Please configure it in the Admin settings first." isAdminFacing=true}
			</div>
		{else}
			<div class="row">
				<div class="col-xs-12 col-sm-6">
					<div class="panel panel-default">
						<div class="panel-heading">
							<h2 class="panel-title">{translate text="Current Settings" isAdminFacing=true}</h2>
						</div>
						<div class="panel-body">
							<div class="form-group">
								<label>{translate text="API Key" isAdminFacing=true}</label>
								<div class="form-control-static">{$apiKey}</div>
							</div>
							<div class="form-group">
								<label>{translate text="Force Full Update" isAdminFacing=true}</label>
								<div class="form-control-static">
									{if $forceFullUpdate}
										<span class="label label-success">{translate text="Enabled" isAdminFacing=true}</span>
									{else}
										<span class="label label-default">{translate text="Disabled" isAdminFacing=true}</span>
									{/if}
								</div>
							</div>
							<div class="form-group">
								<label>{translate text="Extensive Logging" isAdminFacing=true}</label>
								<div class="form-control-static">
									{if $enableExtensiveLogging}
										<span class="label label-success">{translate text="Enabled" isAdminFacing=true}</span>
									{else}
										<span class="label label-default">{translate text="Disabled" isAdminFacing=true}</span>
									{/if}
								</div>
							</div>
							<div class="form-group">
								<p>
									<a href="/Enrichment/NewYorkTimesSettings" class="btn btn-sm btn-default">
										{translate text="Edit Settings" isAdminFacing=true}
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
							<p>{translate text="Click the button below to run the NYT lists updater now." isAdminFacing=true}</p>
							<div class="alert alert-warning">
								{translate text="This will execute the command on the server and may take several minutes to complete. You will be redirected to the update log when finished." isAdminFacing=true}
							</div>
							<button onclick="return AspenDiscovery.Greenhouse.runNYTUpdate('{$siteUrl}');" class="btn btn-primary">
								{translate text="Run NYT Lists Update Now" isAdminFacing=true}
							</button>
							<div id="nytUpdateResult" class="alert alert-info hidden" role="alert">
								<i class="fas fa-spinner fa-spin fa-lg"></i> {translate text="Running the update. Please wait..." isAdminFacing=true}
							</div>
						</div>
					</div>
				</div>
			</div>
		{/if}
	</div>
{/strip}
