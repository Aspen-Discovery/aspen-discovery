{strip}
	<h1>{translate text="Recommended Updates" isAdminFacing=true}</h1>
	<form id="optionalUpdatesForm" action="/Admin/{$action}" method="post" class="form-horizontal">
		<input type="hidden" name="submitting" value="true">
		<div>
			{if empty($optionalUpdates)}
				<div class="alert alert-info">{translate text="There are no remaining recommended updates to be applied." isAdminFacing=true}</div>
			{else}
				<table class="table" aria-label="Recommended Updates">
					<tbody>
						{foreach from=$optionalUpdates item=optionalUpdate key=updateKey name=updates}
							<tr>
								<td><h2>{$smarty.foreach.updates.index+1}) </h2></td>
								<td>{$optionalUpdate->getDescription()}</td>
								<td>
									<br/>
									<select name="updatesToApply[{$updateKey}]" class="form-control" style="width: auto">
										<option value="1" selected>Skip for Now</option>
										<option value="2">Apply Update</option>
										<option value="3">Do not apply Update</option>
									</select>
								</td>
							</tr>
						{/foreach}
					</tbody>
				</table>
				<div class="form-inline">
					<div class="form-group">
						{literal}
						<script type="text/javascript">
							var form = document.getElementById('optionalUpdatesForm');
							form.addEventListener('submit', submitOptionalUpdates);
							function submitDBMaintenance() {
								$('#startOptionalUpdates').prop('disabled', true).addClass('disabled');
								$('#startOptionalUpdates .fa-spinner').removeClass('hidden');
								return true;
							}
						</script>
						{/literal}
						<button type="submit" id="startOptionalUpdates" name="submit" class="btn btn-primary"><i class='fas fa-spinner fa-spin hidden' role='status' aria-hidden='true'></i>&nbsp;{translate text="Apply Updates" isAdminFacing=true}</button>
					</div>
				</div>
			{/if}
		</div>
	</form>
{/strip}