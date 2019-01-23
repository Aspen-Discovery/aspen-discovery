{strip}
	<div id="main-content" class="col-md-12">
		<form name="archiveSubjects" method="post">
			<h3>Archive Private Collections</h3>
			<div class="form-group"><label>Collections that will be shown to the owning library only</label>
				<p class="help-block">List one PID per line</p>
				<textarea name="privateCollections" id="privateCollections" class="form-control">
					{$privateCollections}
				</textarea>

			</div>

			<div class="form-group">
				<button type="submit" class="btn btn-default">Save Changes</button>
			</div>

		</form>
	</div>
{/strip}