{strip}
	<div id="main-content" class="col-md-12">
		<h1>{translate text="OverDrive API Data"}</h1>
		{if count($allSettings) > 1}
			<form name="selectSettings" id="selectSettings" class="form-inline row">
				<div class="form-group col-tn-12">
					<label for="settingId" class="control-label">{translate text="Instance to show stats for"}</label>&nbsp;
					<select id="settingId" name="settingId" class="form-control input-sm" onchange="$('#selectSettings').submit()">
						{foreach from=$allSettings key=settingId item=setting}
							<option value="{$settingId}" {if $settingId == $selectedSettingId}selected{/if}>{$setting->url}</option>
						{/foreach}
					</select>
				</div>
			</form>
		{/if}
		<form class="navbar form-inline row">
			<div class="form-group col-xs-12">
				<label for="overDriveId" class="control-label">{translate text="OverDrive ID"}</label>
				<input id ="overDriveId" type="text" name="id" class="form-control" value="{$overDriveId}">
				<input type="hidden" name="settingId" value="{$selectedSettingId}">
				<button class="btn btn-primary" type="submit">{translate text=Go}</button>
			</div>
		</form>
		{$overDriveAPIData}
	</div>
{/strip}