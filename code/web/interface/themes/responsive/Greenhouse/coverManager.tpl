{strip}
	<div id="main-content" class="col-md-12">
		<h1 id="pageTitle"> {translate text={$pageTitleShort} isAdminFacing=true}</h1>
		<div class="adminHomeOptions">
			<div class="alert alert-info">
				{translate text="Use this tool to reload covers of different types. For most sources, this will remove the cover information so it will be regenerated the next time it's needed. For uploaded covers, this will mark them for reloading while preserving the uploaded file. Note that only sources present in the database will appear in the list below, so some may disappear as they are deleted and may reappear later as they are reloaded." isAdminFacing=true}
			</div>

			<div id="coverReloadResult" class="alert alert-success hidden"></div>

			<div class="row">
				<div class="col-xs-12">
					<div class="panel panel-default">
						<div class="panel-heading">
							<h2 class="panel-title">{translate text="Reload Covers by Source" isAdminFacing=true}</h2>
						</div>
						<div class="panel-body">

							<form id="coverReloadForm">
								<h3>{translate text="Cover Sources" isAdminFacing=true}</h3>
								<div class="checkbox">
									<label>
										<input type="checkbox" name="sources[]" value="default"> default
									</label>
								</div>
								<div class="checkbox">
									<label>
										<input type="checkbox" name="sources[]" value="upload"> upload
									</label>
								</div>

								{foreach from=$coverSources item=source}
									{if $source != 'default' && $source != 'upload'}
										<div class="checkbox">
											<label>
												<input type="checkbox" name="sources[]" value="{$source}"> {$source}
											</label>
										</div>
									{/if}
								{/foreach}

								<div class="form-group mt-4">
									<button type="button" id="processCoversBtn" class="btn btn-primary" style="margin-right: 10px;" onclick="return AspenDiscovery.CoverManager.reloadCoverSources();">{translate text="Process Selected Sources" isAdminFacing=true}</button>
									<button type="button" id="selectAll" class="btn btn-default" style="margin-right: 10px;">{translate text="Select All" isAdminFacing=true}</button>
									<button type="button" id="deselectAll" class="btn btn-default">{translate text="Deselect All" isAdminFacing=true}</button>
								</div>
							</form>

							<script type="text/javascript">
								$(function () {
									$('#selectAll').on('click', function () {
										$('input[name="sources[]"]').prop('checked', true);
									});

									$('#deselectAll').on('click', function () {
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