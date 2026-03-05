{strip}
<div class="row">
	{if !empty($error)}
		<div class="col-xs-12 alert alert-warning">
			{translate text=$error isAdminFacing=true}
		</div>
	{elseif count($matchingSettings) == 0}
		<div class="col-xs-12 alert alert-info">
			{translate text="No matching settings" isAdminFacing=true}
		</div>
	{else}
		<br/>
		<h2>{translate text="Matching settings" isAdminFacing=true}</h2>
		<div class="col-xs-12">
			<table class="table table-striped table-bordered table-hover table-condensed">
				<thead>
					<tr>
						<th>{translate text="Setting" isAdminFacing=true}</th>
						<th>{translate text="Page" isAdminFacing=true}</th>
						<th>{translate text="Section" isAdminFacing=true}</th>
					</tr>
				</thead>
				<tbody>
					{foreach from=$matchingSettings item=$setting}
						<tr>
							<td>
								<strong>{$setting->label}</strong>
							</td>
							<td>
								<a href="/{$setting->module}/{$setting->action}">{$setting->toolTitle}</a>
							</td>
							<td>
								{$setting->section}
							</td>
						</tr>
					{/foreach}
				</tbody>
			</table>
		</div>
		<div class="col-xs-12">
			{translate text="Showing %1% Results" 1=count($matchingSettings) isAdminFacing=true}
		</div>
	{/if}
</div>
{/strip}
