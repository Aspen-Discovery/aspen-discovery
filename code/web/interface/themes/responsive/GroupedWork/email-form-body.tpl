{strip}
<form method="post" action="{$path}" name="popupForm" class="form-horizontal" id="emailForm">
	<div class="alert alert-info">
		<p>
			Sharing via email message will send the title (with a link back to the title) to you so you can easily find it in
			the future.
		</p>
		<p>
			If you would like a call number and location included, please select an edition below.
		</p>
	</div>
	<div class="form-group">
		<label for="related_record" class="col-sm-3">{translate text="Edition"}: </label>
		<div class="col-sm-9">
			<select name="related_record" id="related_record" class="form-control">
				<option selected="selected" value="">{translate text="Select an edition for more details"}</option>
				{foreach from=$relatedRecords key=val item=details}
					<option value="{$details->id}">{$details->format|escape}{if $details->edition} {$details->edition}{/if}{if $details->publisher} {$details->publisher}{/if}{if $detail->publicationDate} {$details->publicationDate}{/if}</option>
				{/foreach}
			</select>
		</div>
	</div>
	<div class="form-group">
		<label for="to" class="col-sm-3">{translate text='To'}:</label>
		<div class="col-sm-9">
			<input type="email" name="to" id="to" size="40" class="required email form-control">
		</div>
	</div>
	<div class="form-group">
		<label for="from" class="col-sm-3">{translate text='From'}:</label>
		<div class="col-sm-9">
			<input type="email" name="from" id="from" size="40" class="email form-control">
		</div>
	</div>
	<div class="form-group">
		<label for="message" class="col-sm-3">{translate text='Message'}:</label>
		<div class="col-sm-9">
			<textarea name="message" id="message" rows="3" cols="40" class="form-control"></textarea>
		</div>
	</div>
</form>
<script type="text/javascript">
	{literal}
	$("#emailForm").validate({
		submitHandler: function(){
			AspenDiscovery.GroupedWork.sendEmail("{/literal}{$id}{literal}")
		}
	});
	{/literal}
</script>
{/strip}