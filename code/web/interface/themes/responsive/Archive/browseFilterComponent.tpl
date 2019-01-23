{strip}
	<div class="archiveComponentContainer nopadding col-sm-12 col-md-6">
		<div class="archiveComponent browseFilterContainer">
			<div class="row archiveComponentBody">
				<div class="archiveComponentBox">
					<a href="#" onclick="return VuFind.Archive.showBrowseFilterPopup('{$pid}', '{$browseFilterFacetName}', '{$browseFilterLabel}')">
						<div class="col-tn-4 col-xs-3 col-md-4 archiveComponentIconContainer">
							<img src="{$browseFilterImage}" width="100" height="100" alt="{$browseFilterLabel}" class="archiveComponentImage">
						</div>
						<div class="col-tn-8 col-xs-9 col-md-8 archiveComponentControls">
							<div class="archiveComponentHeader">{$browseFilterLabel}</div>
						</div>
					</a>
				</div>
			</div>
		</div>
	</div>
{/strip}