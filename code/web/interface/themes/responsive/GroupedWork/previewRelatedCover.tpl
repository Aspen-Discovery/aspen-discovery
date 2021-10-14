{strip}
	<form enctype="multipart/form-data" name="setRelatedCover" id="setRelatedCover" method="post" action="/GroupedWork/{$groupedWorkId}/AJAX">
		<input type="hidden" name="recordId" value="{$recordId}"/>
		<input type="hidden" name="groupedWorkId" value="{$groupedWorkId}"/>
		<input type="hidden" name="recordType" value="{$recordType}"/>
		<input type="hidden" name="method" value="setRelatedCover"/>
		<div class="form-group">
			<div id="recordCover" class="text-center row">
				<img alt="{translate text='Book Cover' inAttribute=true isPublicFacing=true}" class="img-thumbnail" src="/bookcover.php?id={$recordId}&size=medium&type={$recordType}">
			</div>

		</div>
	</form>
{/strip}