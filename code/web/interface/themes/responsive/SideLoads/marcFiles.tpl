{strip}
	<div id="main-content">
		<div class="btn-group">
			<a class="btn btn-sm btn-default" href="/SideLoads/SideLoads?objectAction=edit&amp;id={$id}">{translate text="Edit Profile" isAdminFacing=true}</a>
			{if !empty($additionalObjectActions)}
				{foreach from=$additionalObjectActions item=action}
					{if $smarty.server.REQUEST_URI != $action.url}
						<a class="btn btn-default btn-sm" href='{$action.url}'>{$action.text}</a>
					{/if}
				{/foreach}
			{/if}
			<a class="btn btn-sm btn-default" href='/SideLoads/SideLoads?objectAction=list'>{translate text="Return to List" isAdminFacing=true}</a>
		</div>
		<h1>{$SideLoadName}</h1>
		<table class="table table-striped table-bordered">
			<tr>
				<th>{translate text="File Name" isAdminFacing=true}</th>
				<th>{translate text="Date" isAdminFacing=true}</th>
				<th>{translate text="Size (bytes)" isAdminFacing=true}</th>
				<th></th>
			</tr>
			{foreach from=$files key=file item=fileData}
				<tr id="file{$fileData.index}">
					<td><a href="/SideLoads/DownloadMarc?id={$id}&file={$file|urlencode}">{$file}</a></td>
					<td>{$fileData.date|date_format:"%D %T"}</td>
					<td>{$fileData.size|number_format}</td>
					<td><a class="btn btn-sm btn-danger" onclick="return AspenDiscovery.SideLoads.deleteMarc('{$id}', '{$file|escape:"javascript"}', {$fileData.index});">{translate text="Delete" isAdminFacing=true}</a></td>
				</tr>
			{foreachelse}
				<tr>
					<td>{translate text="No Marc Files Found" isAdminFacing=true}</td>
				</tr>
			{/foreach}
		</table>
		{if !$sideload->isReadOnly()}
			<a class="btn btn-primary" href="/SideLoads/UploadMarc?id={$id}">
				{translate text="Upload MARC file" isAdminFacing=true}
			</a>
		{/if}
	</div>
{/strip}
