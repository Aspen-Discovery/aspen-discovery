{strip}
	<input type="hidden" name="print" id="print" value="true">
	<input type="hidden" name="listId" id="listId" value="{$printListId}">
	<div class="row">
		<div class="col-xs-12">
			<p>{translate text="Select the elements you'd like to print" isPublicFacing=true}</p>
		</div>
		<div class="col-md-4">
			<h4 class="bold">Layout Options</h4>
			<div class="form-group checkbox">
				<label for="printLibraryName">
					<input type="checkbox" name="printLibraryName" id="printLibraryName" checked>
					<strong>{translate text="Library Name" isPublicFacing=true}</strong>
				</label>
			</div>
			<div class="form-group checkbox">
				<label for="printLibraryLogo">
					<input type="checkbox" name="printLibraryLogo" id="printLibraryLogo" checked>
					<strong>{translate text="Library Logo" isPublicFacing=true}</strong>
				</label>
			</div>
		</div>
		<div class="col-md-4">
			<h4 class="bold">List Options</h4>
			<div class="form-group checkbox">
				<label for="listAuthor">
					<input type="checkbox" name="listAuthor" id="listAuthor" checked>
					<strong>{translate text="List Author" isPublicFacing=true}</strong>
				</label>
			</div>
			<div class="form-group checkbox">
				<label for="listDescription">
					<input type="checkbox" name="listDescription" id="listDescription" checked>
					<strong>{translate text="List Description" isPublicFacing=true}</strong>
				</label>
			</div>
		</div>
		<div class="col-md-4">
			<h4 class="bold">Entry Options</h4>
			<div class="form-group checkbox">
				<label for="covers">
					<input type="checkbox" name="covers" id="covers" checked>
					<strong>{translate text="Covers" isPublicFacing=true}</strong>
				</label>
			</div>
			<div class="form-group checkbox">
				<label for="series">
					<input type="checkbox" name="series" id="series" checked>
					<strong>{translate text="Series Information" isPublicFacing=true}</strong>
				</label>
			</div>
			<div class="form-group checkbox">
				<label for="formats">
					<input type="checkbox" name="formats" id="formats" checked>
					<strong>{translate text="Formats" isPublicFacing=true}</strong>
				</label>
			</div>
			<div class="form-group checkbox">
				<label for="description">
					<input type="checkbox" name="description" id="description" checked>
					<strong>{translate text="Descriptions" isPublicFacing=true}</strong>
				</label>
			</div>
			<div class="form-group checkbox">
				<label for="notes">
					<input type="checkbox" name="notes" id="notes" checked>
					<strong>{translate text="Notes" isPublicFacing=true}</strong>
				</label>
			</div>
			<div class="form-group checkbox">
				<label for="rating">
					<input type="checkbox" name="rating" id="rating">
					<strong>{translate text="Star Rating" isPublicFacing=true}</strong>
				</label>
			</div>
			<div class="form-group checkbox">
				<label for="holdings">
					<input type="checkbox" name="holdings" id="holdings">
					<strong>{translate text="Holdings" isPublicFacing=true}</strong>
				</label>
			</div>
		</div>
	</div>
{/strip}