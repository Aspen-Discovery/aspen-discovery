{strip}
	<div id="main-content">
		<div class="btn-group">
			<a class="btn btn-sm btn-default" href="/SideLoads/SideLoads?objectAction=edit&amp;id={$id}">Edit Profile</a>
			{foreach from=$additionalObjectActions item=action}
				{if $smarty.server.REQUEST_URI != $action.url}
					<a class="btn btn-default btn-sm" href='{$action.url}'>{$action.text}</a>
				{/if}
			{/foreach}
			<a class="btn btn-sm btn-default" href='/SideLoads/SideLoads?objectAction=list'>Return to List</a>
		</div>
		<h1>{$IndexProfileName}</h1>
		<table class="table table-striped table-bordered">
			<tr>
				<th>File Name</th>
				<th>Date</th>
				<th>Size (bytes)</th>
{*				<th></th>*}
			</tr>
			{foreach from=$files key=file item=fileData}
				<tr>
					<td><a href="/SideLoads/DownloadMarc?id={$id}&file={$file|urlencode}">{$file}</a></td>
					<td>{$fileData.date|date_format:"%D %T"}</td>
					<td>{$fileData.size|number_format}</td>
{*					<td><a class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this file?');" href="/SideLoads/DeleteMarc?id={$id}&file={$file|urlencode}">Delete</a> </td>*}
				</tr>
			{foreachelse}
				<tr>
					<td>No Marc Files Found</td>
				</tr>
			{/foreach}
		</table>
		<a class="btn btn-primary" href="/SideLoads/UploadMarc?id={$id}">
			{translate text="Upload MARC file"}
		</a>
	</div>
{/strip}