{strip}
	<div id="main-content">
		<div class="btn-group">
			<a class="btn btn-sm btn-default" href="/ILS/IndexingProfiles?objectAction=edit&amp;id={$id}">{translate text="Edit Profile" isAdminFacing=true}</a>
			{foreach from=$additionalObjectActions item=action}
				{if $smarty.server.REQUEST_URI != $action.url}
					<a class="btn btn-default btn-sm" href='{$action.url}'>{translate text=$action.text isAdminFacing=true}</a>
				{/if}
			{/foreach}
			<a class="btn btn-sm btn-default" href='/ILS/IndexingProfiles?objectAction=list'>{translate text="Return to List" isAdminFacing=true}</a>
		</div>
		<h1>{$IndexProfileName}</h1>
		<table class="table table-striped table-bordered">
			<tr>
				<th>{translate text="File Name" isAdminFacing=true}</th>
				<th>{translate text="Date" isAdminFacing=true}</th>
			</tr>
			{foreach from=$files key=file item=fileDate}
				<tr>
					<td>{$file}</td>
					<td>{$fileDate|date_format}</td>
				</tr>
			{foreachelse}
				<tr>
					<td>{translate text="No Marc Files Found" isAdminFacing=true}</td>
				</tr>
			{/foreach}
		</table>

	</div>
{/strip}