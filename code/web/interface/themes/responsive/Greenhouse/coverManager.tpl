{strip}
	<div id="main-content" class="col-md-12">
		<h1>{translate text="Cover Manager" isAdminFacing=true}</h1>
		<div class="adminHomeOptions">
			<div class="alert alert-info">
				{translate text="Use this tool to reload covers of different types. For most sources, this will remove the cover information so it will be regenerated the next time it's needed. For uploaded covers, this will mark them for reloading while preserving the uploaded file." isAdminFacing=true}
			</div>

			<div id="coverReloadResult" class="alert alert-success hidden"></div>

			{if !empty($reloadMessage)}
				<div class="alert alert-success">
					{$reloadMessage}
				</div>
			{/if}

			<div class="row">
				<div class="col-xs-12">
					<div class="panel panel-default">
						<div class="panel-heading">
							<h2 class="panel-title">{translate text="Reload Covers by Source" isAdminFacing=true}</h2>
						</div>
						<div class="panel-body">

							<form id="coverReloadForm">
								<h3>{translate text="External Cover Sources" isAdminFacing=true}</h3>
								<div class="checkbox">
									<label>
										<input type="checkbox" name="sources[]" value="default"> {translate text="Default Covers" isAdminFacing=true}
									</label>
								</div>
								<div class="checkbox">
									<label>
										<input type="checkbox" name="sources[]" value="syndetics"> {translate text="Syndetics Covers" isAdminFacing=true}
									</label>
								</div>
								<div class="checkbox">
									<label>
										<input type="checkbox" name="sources[]" value="marcRecord"> {translate text="MARC Record Covers" isAdminFacing=true}
									</label>
								</div>
								<div class="checkbox">
									<label>
										<input type="checkbox" name="sources[]" value="omdb_title"> {translate text="OMDB Title Covers" isAdminFacing=true}
									</label>
								</div>
								<div class="checkbox">
									<label>
										<input type="checkbox" name="sources[]" value="omdb_title_year"> {translate text="OMDB Title+Year Covers" isAdminFacing=true}
									</label>
								</div>
								<div class="checkbox">
									<label>
										<input type="checkbox" name="sources[]" value="coce_amazon"> {translate text="COCE Amazon Covers" isAdminFacing=true}
									</label>
								</div>
								<div class="checkbox">
									<label>
										<input type="checkbox" name="sources[]" value="coce_google_books"> {translate text="COCE Google Books Covers" isAdminFacing=true}
									</label>
								</div>
								<div class="checkbox">
									<label>
										<input type="checkbox" name="sources[]" value="coce_open_library"> {translate text="COCE Open Library Covers" isAdminFacing=true}
									</label>
								</div>
								<div class="checkbox">
									<label>
										<input type="checkbox" name="sources[]" value="overdrive"> {translate text="OverDrive Covers" isAdminFacing=true}
									</label>
								</div>

								<h3 class="mt-4">{translate text="Uploaded Covers" isAdminFacing=true}</h3>
								<div class="checkbox">
									<label>
										<input type="checkbox" name="sources[]" value="upload"> {translate text="Uploaded Covers" isAdminFacing=true}
									</label>
								</div>

								<div class="form-group mt-4">
									<button type="button" id="processCoversBtn" class="btn btn-primary" style="margin-right: 10px;" onclick="return AspenDiscovery.Greenhouse.reloadCoverSources();">{translate text="Process Selected Sources" isAdminFacing=true}</button>
									<button type="button" id="selectAll" class="btn btn-default" style="margin-right: 10px;">{translate text="Select All" isAdminFacing=true}</button>
									<button type="button" id="deselectAll" class="btn btn-default">{translate text="Deselect All" isAdminFacing=true}</button>
								</div>
							</form>

							<script type="text/javascript">
								$(document).ready(function() {
									$('#selectAll').click(function() {
										$('input[name="sources[]"]').prop('checked', true);
									});

									$('#deselectAll').click(function() {
										$('input[name="sources[]"]').prop('checked', false);
									});
								});
							</script>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
{/strip}
