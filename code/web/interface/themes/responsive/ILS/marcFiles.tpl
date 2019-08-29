{strip}
	<div id="main-content">
		<div class="btn-group">
			<a class="btn btn-sm btn-default" href="/ILS/IndexingProfiles?objectAction=edit&amp;id={$id}">Edit Profile</a>
			{foreach from=$additionalObjectActions item=action}
				{if $smarty.server.REQUEST_URI != $action.url}
					<a class="btn btn-default btn-sm" href='{$action.url}'>{$action.text}</a>
				{/if}
			{/foreach}
			<a class="btn btn-sm btn-default" href='/ILS/IndexingProfiles?objectAction=list'>Return to List</a>
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
					<td>{$fileDate|date_format}</td>
				</tr>
			{foreachelse}
				<tr>
					<td>No Marc Files Found</td>
				</tr>
			{/foreach}
		</table>

	</div>
{/strip}