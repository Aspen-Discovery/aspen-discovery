{strip}
<form enctype="multipart/form-data" name="downloadFile" id="downloadFile" method="post" onsubmit="return AspenDiscovery.GroupedWork.downloadSelectedFile();">
	<input type="hidden" name="id" id="id" value="{$id}"/>
	<input type="hidden" name="fileType" id="fileType" value="{$fileType}"/>
	<div class="form-group">
		<div class="form-group">
			<label for="selectedFile">
				{translate text="Select a file to download"}
			</label>
			<select name="selectedFile" id="selectedFile" class="form-control">
				{foreach from=$validFiles item=title key=fileId}
					<option value="{$fileId}">{$title}</option>
				{/foreach}
			</select>
		</div>
	</div>
</form>
{/strip}