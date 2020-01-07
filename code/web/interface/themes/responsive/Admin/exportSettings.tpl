{strip}
	<div id="main-content" class="col-tn-12 col-xs-12">
		<h1>Export Aspen Discovery Settings</h1>
		<p>This functionality is designed to export all of the settings needed to clone an Aspen Discovery instance.&nbsp;
			Once exported, the file will need to be imported into a new instance.&nbsp;
		</p>
		<form method="post">
			<div class="form-group">
				<div class="checkbox">
					<label for="exportSettings" class="control-label"><input type="checkbox" name="exportSettings" id="exportSettings"> Export Settings</label>
				</div>
			</div>
			<div class="form-group">
				<div class="checkbox">
					<label for="exportUserData" class="control-label"><input type="checkbox" name="exportUserData" id="exportUserData"> Export User Data</label>
				</div>
			</div>
			<div class="form-group">
				<button class="btn btn-default btn-primary" name="submit" id="submit">Export Settings</button>
			</div>
		</form>
	</div>
{/strip}