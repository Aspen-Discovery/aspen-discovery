	<div id="main-content">
		<h1>Copy Location Data</h1>
		{if count($allLocations) == 0}
			<div>Sorry, there are no locations available for you to copy data from.</div>
		{else}
			<form action="/Admin/Locations" method="get" class="form">
				<div>
					<input type="hidden" name="id" value="{$id}"/>
					<input type="hidden" name="objectAction" value="copyDataFromLocation"/>
					<div class="input-group">
						<label for="locationToCopyFrom" class="control-label">Select a location to copy data from:</label>
						<select id="locationToCopyFrom" name="locationToCopyFrom" class="form-control">
							{foreach from=$allLocations item=location}
								<option value="{$location->locationId}">{$location->displayName}</option>
							{/foreach}
						</select>
					</div>
					<div class="input-group">
						<label for="copyFacets" class="control-label"><input type="checkbox" name="copyFacets" id="copyFacets"/> Copy Facets</label>
					</div>
					<div class="input-group">
						<label for="copyBrowseCategories" class="control-label"><input type="checkbox" name="copyBrowseCategories" id="copyBrowseCategories"/> Copy Browse Categories</label>
					</div>
					<div class="input-group">
						<input type="submit" name="submit" value="Copy Data" class="btn btn-primary"/>
					</div>
				</div>
			</form>
		{/if}
	</div>
