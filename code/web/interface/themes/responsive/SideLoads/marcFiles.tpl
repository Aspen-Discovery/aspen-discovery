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
			</tr>
			{foreach from=$files key=file item=fileDate}
				<tr>
					<td>{$file}</td>
					<td>{$fileDate|date_format:"%D %T"}</td>
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