{strip}
	<div class="row">
		<div class="col-xs-12 col-md-9">
			<h1 id="pageTitle">{$pageTitleShort}</h1>
		</div>
		<div class="col-xs-12 col-md-3 help-link">
			{if !empty($instructions)}<a href="{$instructions}" target="_blank"><i class="fas fa-question-circle" role="presentation"></i>&nbsp;{translate text="Documentation" isAdminFacing=true}</a>{/if}
		</div>
	</div>

	<div id="main-content" class="col-md-12">
		<h2 id="notificationTool">{translate text="Send A Test Notification" isAdminFacing=true}</h2>
		<form class="row">
			<div class="form-group col-xs-12">
				<label for="pushToken" class="control-label">{translate text="Recipient Expo Push Token" isAdminFacing=true}</label>
					<input name="pushToken" type="text" class="form-control">
			</div>
			<div class="form-group col-xs-12">
				<label for="testTitle" class="control-label">{translate text="Title" isAdminFacing=true}</label>
					<input name="testTitle" type="text" class="form-control">
			</div>
			<div class="form-group col-xs-12">
				<label for="testBody" class="control-label">{translate text="Body" isAdminFacing=true}</label>
				<textarea rows="5" cols="40" name="testBody" class="form-control"></textarea>
			</div>
			<div class="form-group col-xs-12">
				<input type="hidden" id="sendNotification" name="sendNotification" value="true">
				<button class="btn btn-primary" type="submit">{translate text="Send Notification" isAdminFacing=true}</button>
			</div>
		</form>
		{$notificationResponse}

		<h2 id="receiptTool">{translate text="Check Notification Receipt" isAdminFacing=true}</h2>
		<form class="row">
			<div class="form-group col-xs-12">
				<label for="receiptId" class="control-label">{translate text="Receipt ID" isAdminFacing=true}</label>
				<input name="receiptId" type="text" class="form-control">
				<span id="receiptIdHelpBlock" class="help-block"><small><i class="fas fa-info-circle"></i> {translate text="It's recommended to wait 15 minutes before checking a receipt status. Receipts are only valid for 24 hours after the notification is sent." isAdminFacing=true}</small></span>
			</div>
			<div class="form-group col-xs-12">
				<input type="hidden" id="getNotificationReceipt" name="getNotificationReceipt" value="true">
				<button class="btn btn-primary" type="submit">{translate text="Get Receipt" isAdminFacing=true}</button>
			</div>
		</form>
		{$receiptResponse}
	</div>
{/strip}
