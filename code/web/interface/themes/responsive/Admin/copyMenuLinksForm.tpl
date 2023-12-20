<form id="copyMenuLinksForm" role="form">
	<input type="hidden" name="sourceLibraryId" id="sourceLibraryId" value="{$sourceLibraryId}"/>
	<div class="row-no-gutters">
		<div class="col-md-6">
			<div class="form-group">
				<label class="control-label">{translate text="Menu Links to be copied" isAdminFacing=true}</label>
			</div>
			<div class="form-group checkbox">
				<label for="selectAllMenuItems">
					<input type="checkbox" name="selectAllMenuItems" id="selectAllMenuItems" onchange="AspenDiscovery.toggleCheckboxes('.menuLink', '#selectAllMenuItems');" checked>
					<strong>{translate text="Select/Deselect All" isAdminFacing=true}</strong>
				</label>
			</div>

			{foreach from=$menuLinks item=$menuLink}
				<div class="form-group checkbox">
					<label>
						<input type="checkbox" name="menuLink[{$menuLink->id}]" checked="checked" class="menuLink"> {if !empty($menuLink->category)}{$menuLink->category} - {/if} {$menuLink->linkText}
					</label>
				</div>
			{/foreach}
		</div>


		<div class="col-md-6">
			<div class="form-group">
				<label class="control-label">{translate text="Copy to these libraries" isAdminFacing=true}</label>
			</div>
			<div class="form-group checkbox">
				<label for="selectAllLibraries">
					<input type="checkbox" name="selectAllLibraries" id="selectAllLibraries" onchange="AspenDiscovery.toggleCheckboxes('.library', '#selectAllLibraries');" checked>
					<strong>{translate text="Select/Deselect All" isAdminFacing=true}</strong>
				</label>
			</div>
			{foreach from=$libraryList key=$libraryId item=$libraryDisplayName}
				<div class="form-group checkbox">
					<label>
						<input type="checkbox" name="library[{$libraryId}]" checked="checked" class="library"> {$libraryDisplayName}
					</label>
				</div>
			{/foreach}
		</div>
	</div>

</form>