{strip}
	<div id="main-content" class="col-md-12">
			<h1>Collection Report</h1>
			{if isset($errors)}
				{foreach from=$errors item=error}
					<div class="error">{$error}</div>
				{/foreach}
			{/if}
			<form class="form form-inline">

				{html_options name=location options=$locationLookupList selected=$selectedLocation class="form-control input-sm"}
				&nbsp;
				<input type="submit" name="download" value="Download CSV" class="btn btn-sm btn-info"/>
				&nbsp;
			</form>
	</div>
{/strip}
