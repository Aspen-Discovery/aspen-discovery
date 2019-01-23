{strip}
	<div id="main-content" class="col-md-12">
		<h3>OverDrive API Data</h3>
		<form class="navbar form-inline row">
			<div class="form-group col-xs-12">
				<label for="overDriveId" class="control-label">OverDrive ID:</label>
				<input id ="overDriveId" type="text" name="id" class="form-control">
				<button class="btn btn-primary" type="submit">Go</button>
			</div>
		</form>
		{$overDriveAPIData}
	</div>
{/strip}