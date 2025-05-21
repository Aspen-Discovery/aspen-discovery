<form>
	<div class="row">
		{if count($settings) > 1}
			<div class="col-sm-3 col-md-3 col-lg-2">
				<div class="form-group">
					<label for="settingToShow">{translate text='Setting' isAdminFacing=true}</label>
					<select id="settingToShow" name="settingToShow" class="form-control input-sm">
						<option value="-1" {if $selectedSetting == -1}selected{/if}>{translate text='All' isAdminFacing=true}</option>
						{foreach from=$settings item=settingName key=settingId}
							<option value="{$settingId}" {if $selectedSetting == $settingId}selected{/if}>{$settingName}</option>
						{/foreach}
					</select>
				</div>
			</div>
		{/if}
		<div class="col-sm-5 col-md-4">
			<div class="form-group">
				<label for="pageSize">{translate text='Entries Per Page' isAdminFacing=true}</label>
				<select id="pageSize" name="pageSize" class="pageSize form-control input-sm">
					<option value="30"{if $recordsPerPage == 30} selected="selected"{/if}>30</option>
					<option value="50"{if $recordsPerPage == 50} selected="selected"{/if}>50</option>
					<option value="75"{if $recordsPerPage == 75} selected="selected"{/if}>75</option>
					<option value="100"{if $recordsPerPage == 100} selected="selected"{/if}>100</option>
				</select>
			</div>
		</div>
		<div class="col-sm-3 col-md-3 col-lg-2">
			<div class="form-group">
				<label for="processedLimit">{translate text='Min Processed' isAdminFacing=true}</label>
				<div class="input-group-sm input-group">
					<input id="processedLimit" name="processedLimit" type="number" min="0" class="form-control input-sm" {if !empty($processedLimit)} value="{$processedLimit}"{/if}>
				</div>
			</div>
		</div>
		<div class="col-sm-4 col-md-4 col-lg-3">
			<div class="form-group">
				<label for="showErrorsOnly">{translate text='Show Errors Only' isAdminFacing=true}</label>
				<div class="input-group-sm input-group">
					<input type='checkbox' name='showErrorsOnly' id='showErrorsOnly' data-on-text="{translate text='Errors Only' inAttribute=true isAdminFacing=true}" data-off-text="{translate text='All Records' inAttribute=true isAdminFacing=true}" data-switch="" {if !empty($showErrorsOnly)}checked{/if}/>
				</div>
			</div>
		</div>

	</div>
	<div class="row">
		<div class="col-sm-2 col-md-4">
			<div class="form-group">
				<button class="btn btn-primary btn-sm" type="submit">{translate text="Apply" isAdminFacing=true}</button>
			</div>
		</div>
	</div>
</form>
<script type="text/javascript">
	{literal}
	$(function(){ $('input[type="checkbox"][data-switch]').bootstrapSwitch()});
	{/literal}
</script>
