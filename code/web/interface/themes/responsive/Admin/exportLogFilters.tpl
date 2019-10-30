<form>
	<div class="row">
		<div class="col-sm-5 col-md-4">
			<div class="form-group">
				<label for="pageSize">{translate text='Entries Per Page'}</label>
				<select id="pageSize" name="pageSize" class="pageSize form-control input-sm">
					<option value="30"{if $recordsPerPage == 30} selected="selected"{/if}>30</option>
					<option value="50"{if $recordsPerPage == 50} selected="selected"{/if}>50</option>
					<option value="75"{if $recordsPerPage == 75} selected="selected"{/if}>75</option>
					<option value="100"{if $recordsPerPage == 100} selected="selected"{/if}>100</option>
				</select>
			</div>
		</div>
		<div class="col-sm-5 col-md-4">
			<div class="form-group">
				<label for="processedLimit">{translate text='Min Processed'}</label>
				<div class="input-group-sm input-group">
					<input id="processedLimit" name="processedLimit" type="number" min="0" class="form-control input-sm" {if !empty($processedLimit)} value="{$processedLimit}"{/if}>
				</div>
			</div>
		</div>

	</div>
	<div class="row">
		<div class="col-sm-2 col-md-4">
			<div class="form-group">
				<button class="btn btn-primary btn-sm" type="submit">Apply</button>
			</div>
		</div>
	</div>
</form>