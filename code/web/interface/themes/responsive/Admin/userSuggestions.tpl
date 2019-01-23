
	<div id="main-content" class="col-md-12">
		<h3>User Suggestions</h3>
		{if $showHidden == false}
			<a href='{$path}/Admin/UserSuggestions'>Hide Hidden Suggestions</a>
		{else}
			<a href='{$path}/Admin/UserSuggestions?showHidden'>Show Hidden Suggestions</a>
		{/if}
		<br />
		<form id='suggestionsForm' method="post" action="/Admin/UserSugestions">
			<div class='adminTableRegion' style='overflow:auto;'>
				<table class="table table-bordered table-striped" id='suggestions'>
					<thead>
						<tr>
							<th id='nameHeader' class='headerCell'><label title='The name of the user who entered the suggestion'>Name</label></th>
							<th id='emailHeader' class='headerCell'><label title='The email address of the user who entered the suggestion'>E-mail</label></th>
							<th id='suggestionHeader' class='headerCell'><label title='The text of the suggestion'>Suggestion</label></th>
							<th id='enteredOnHeader' class='headerCell'><label title='When the suggestion was entered'>Entered On</label></th>
							<th id='hideHeader' class='headerCell'><label title='Check to remove the hide the suggestion from view.'>Hide?</label></th>
							<th id='notesHeader' class='headerCell'><label title='Internal notes by an administrator if needed.'>Internal Notes</label></th>
							<th id='deleteHeader' class='headerCell'><label title='Check to remove the Suggestion from the system.'>Delete?</label></th>
						</tr>
					</thead>
					<tbody>
						{if isset($suggestions) && is_array($suggestions)}
							{foreach from=$suggestions item=suggestion key=id}
							<tr>
								<td><input type='hidden' name='id[{$id}]' value='{$id}'/>{$suggestion->name|escape:"htmlall"}</td>
								<td><a href="mailto:{$suggestion->email|escape:"hex"}">{$suggestion->email|escape:"hexentity"}</a></td>
								<td>{$suggestion->suggestion|escape:"htmlall"}</td>
								<td>{$suggestion->enteredOn|date_format:"%Y-%m-%d %H:%M"}</td>
								<td class="centered"><input type='checkbox' name='hide[{$id}]' {if $suggestion->hide == 1}checked='checked'{/if} /></td>
								<td class='notes'><textarea rows='2' cols='20' name='internalNotes[{$id}]'>{$suggestion->internalNotes|escape:"htmlall"}</textarea></td>
								<td class="centered"><input type='checkbox' name='delete[{$id}]'/></td>
							</tr>
							{/foreach}
						{/if}
					</tbody>
				</table>
				<input type="submit" name="submit" value="Save Changes" class="btn btn-primary"/>
			</div>
		</form>
	</div>
