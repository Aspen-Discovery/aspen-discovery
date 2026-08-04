{strip}
	<div class="row">
		<div class="col-xs-12 col-md-9">
			<h1 id="pageTitle">{$pageTitleShort}</h1>
		</div>
		<div class="col-xs-12 col-md-3 help-link">
            {if !empty($instructions)}<a href="{$instructions}" target="_blank">
				<i class="fas fa-question-circle" role="presentation"></i>
				&nbsp;{translate text="Documentation" isAdminFacing=true}</a>{/if}
		</div>
	</div>
	<div id="main-content" class="col-md-12">
		<h2 id="notificationTool">{translate text="Send A Test Notification" isAdminFacing=true}</h2>
		<form class="row">
			<div class="form-group">
				<div class="col-xs-12">
					<label for="testPatronBarcode" class="control-label">
                        {translate text="Barcode or Username of Patron to Send Notification To" isAdminFacing=true}
					</label>
				</div>
				<div class="col-xs-5">
					<input id="testPatronBarcode" name="testPatronBarcode" type="text" class="form-control">

				</div>
				<div class="col-xs-2">
					<input type="button" class="btn btn-info btn-block" value="Find Devices"
					       onclick="return AspenDiscovery.Admin.getNotificationDevicesForUser('firebase');">

				</div>
				<div id="patronDevices" class="col-xs-12" style="margin-top: 1em"></div>
				<div class="col-xs-12">
					<div id="error" class="alert alert-warning" style="display: none; margin-top: 1em"></div>
				</div>
			</div>
			<div id="notificationSetup" style="display: none">
				<div class="form-group col-xs-12" style="padding-top:2em">
					<label for="testTitle" class="control-label">{translate text="Title" isAdminFacing=true}</label>
					<input name="testTitle" type="text" class="form-control">
				</div>
				<div class="form-group col-xs-12">
					<label for="testBody" class="control-label">{translate text="Body" isAdminFacing=true}</label>
					<textarea rows="5" cols="40" name="testBody" class="form-control"></textarea>
				</div>
				<div class="form-group col-xs-12">
					<input type="hidden" id="sendNotification" name="sendNotification" value="true">
					<button class="btn btn-primary"
					        type="submit">{translate text="Send Notification" isAdminFacing=true}</button>
				</div>
			</div>
		</form>
        {$notificationResponse}
	</div>
{/strip}
