{strip}
	<div id="page-content" class="content">
		<div id="main-content">
			{if !empty($error)}
				<div class="alert alert-danger">{$error}</div>
			{else}
				<div class="record">

					<h3 id="resourceTitle">{$recordDriver->getTitle()|escape:"html"}</h3>

					<form method="post" id="listEntryEditForm" action="/MyAccount/Edit" class="form-horizontal">
						<input type="hidden" name="listEntry" value="{$listEntry->id}">
						<input type="hidden" name="list_id" value="{$list->id}">
						<div>
							<div class="form-group">
								<label for="listName" class="col-sm-3">{translate text='List'}: </label>
								<div class="col-sm-9">{$list->title|escape:"html"}</div>
							</div>

							<div class="form-group">
								<label for="listNotes" class="col-sm-3">{translate text='Notes'}: </label>
								<div class="col-sm-9">
									<textarea id="listNotes" name="notes" rows="3" cols="50" class="form-control">{$listEntry->notes|escape:"html"}</textarea>
								</div>
							</div>

							<div class="form-group">
								<div class="col-sm-3"></div>
								<div class="col-sm-9">
									<input type="submit" name="submit" value="{translate text='Save'}" class="btn btn-primary">
								</div>
							</div>
						</div>
					</form>
				</div>
			{/if}
		</div>
	</div>
{/strip}