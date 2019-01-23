{strip}
	<div id="main-content" class="col-md-12">
		<form name="archiveSubjects" method="post">
			<h3>Archive Subjects</h3>
			<div class="form-group">
				<label for="subjectsToIgnore">Subjects to Ignore for Explore More</label>
				<p class="help-block">These subjects will not be used when linking from the archive to the catalog or EBSCO.  Enter one subject per line.</p>
				<textarea name="subjectsToIgnore" id="subjectsToIgnore" class="form-control">
					{$subjectsToIgnore}
				</textarea>
			</div>

			<div class="form-group">
				<label for="subjectsToRestrict">Subjects to Restrict for Explore More</label>
				<p class="help-block">These subjects will only be used if more desirable subjects are not found when linking from the archive to the catalog or EBSCO.  Enter one subject per line.</p>
				<textarea name="subjectsToRestrict" id="subjectsToRestrict" class="form-control">
					{$subjectsToRestrict}
				</textarea>
			</div>

			<div class="form-group">
				<button type="submit" class="btn btn-default">Save Changes</button>
			</div>

		</form>
	</div>
{/strip}