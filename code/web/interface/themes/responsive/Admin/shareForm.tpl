{strip}
	<div class="col-xs-12">
		<div class="row">
			<div class="col-xs-12 col-md-9">
				<h1 id="pageTitle">{translate text="Sharing %1%" 1=$objectName isAdminFacing=true}</h1>
			</div>
		</div>
		<div class="row">
			<div class="col-xs-12">
				<div class="btn-toolbar" role="toolbar">
					<div class="btn-group" role="group">
						{if !empty($showReturnToList)}
							<a class="btn btn-default" href='/{$module}/{$toolName}?objectAction=list'><i class="fas fa-arrow-alt-circle-left" role="presentation"></i> {translate text="Return to List" isAdminFacing=true}</a>
						{/if}
						{if !empty($id)}
							<a class="btn btn-default" href='/{$module}/{$toolName}?id={$id}&amp;objectAction=edit'><i class="fas fa-edit" role="presentation"></i> {translate text="Edit" isAdminFacing=true}</a>
						{/if}
					</div>
				</div>
			</div>
		</div>
		<form id="shareContentForm" method="post" class="form-horizontal">
			<input type="hidden" name="objectAction" value="shareToCommunity">
			<div class="form-group">
				<label for="contentName" class="control-label">{translate text='Name' isPublicFacing=true}</label>
				<input type="text" class="form-control" name="contentName" value="{$objectName}"/>
			</div>
			<div class="form-group">
				<label for="contentDescription" class="control-label">{translate text='Description' isPublicFacing=true}</label>
				<textarea type="text" class="form-control" name="contentDescription"></textarea>
			</div>
			<div class="form-group">
				<button type="submit" name="submitReturnToList" value="Save Changes and Return" class="btn btn-primary"><i class="fas fa-file-upload" role="presentation"></i> {translate text="Share" isAdminFacing=true}</button>
			</div>
		</form>
	</div>
{/strip}