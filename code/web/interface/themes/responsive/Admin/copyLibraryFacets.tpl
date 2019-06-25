	<div id="main-content">
		<h1>{translate text="Copy Library %1% Facets" 1="{$facetType|capitalize}"</h1>
		{if count($allLibraries) == 0}
			<div class="alert alert-warning">Sorry, there are no libraries available for you to copy {$facetType} facets from.</div>
		{else}
			<form action="/Admin/Libraries" method="get" class="form-inline">
				<div>
					<input type="hidden" name="id" value="{$id}">
					<input type="hidden" name="objectAction" value="{$objectAction}">
					<div class="form-group">
					<label for="libraryToCopyFrom">Select a library to copy {$facetType} facets from:</label>
					<select id="libraryToCopyFrom" name="libraryToCopyFrom" class="form-control">
						{foreach from=$allLibraries item=library}
							<option value="{$library->libraryId}">{$library->displayName}</option>
						{/foreach}
					</select>
					</div>
					<input type="submit" name="submit" value="Copy Facets" class="btn btn-primary">
				</div>
			</form>
		{/if}
	</div>
