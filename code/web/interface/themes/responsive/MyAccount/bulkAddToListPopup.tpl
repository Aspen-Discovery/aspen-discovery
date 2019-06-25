<div>
	<div id="addToListComments" class="help-block">
		Please enter one or more titles or ISBNs to add to your list.
		Each title or ISBN should be on its own line.
		We will search the catalog for each title and add the first matching title for each line to your list.
	</div>
	<form method="post" name="bulkAddToList" id="bulkAddToList" action="{$path}/MyAccount/MyList/{$listId}" class="form">
		<div>
			<input type="hidden" name="myListActionHead" value="bulkAddTitles"/>
			<textarea rows="5" cols="40" name="titlesToAdd" class="form-control"></textarea>
		</div>
	</form>
</div>