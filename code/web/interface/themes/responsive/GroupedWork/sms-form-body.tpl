<form method="post" action="{$path}" name="popupForm" class="form-horizontal" id="smsForm">
	<div class="alert alert-info">
		<p>
			Sharing via SMS text message will send the title (with a link back to the title) to you so you can easily find it in the future.
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
					<option value="{$details.id}">{$details.format|escape}{if $details.edition} {$details.edition}{/if}{if $details.publisher} {$details.publisher}{/if}{if $details.publicationDate} {$details.publicationDate}{/if}</option>
				{/foreach}
			</select>
		</div>
	</div>
	<div class="form-group">
		<label for="sms_phone_number" class="col-sm-3">{translate text="Number"}: </label>
		<div class="col-sm-9">
      <input type="text" name="to" id="sms_phone_number" {*value="{translate text="sms_phone_number"}"*}
				      class="form-control"
             placeholder="{translate text="sms_phone_number"}"
   {*     onfocus="if (this.value=='{translate text="sms_phone_number"}') this.value=''"
        onblur="if (this.value=='') this.value='{translate text="sms_phone_number"}'"*}
      >

    </div>
  </div>
	<div class="form-group">
		<label for="provider" class="col-sm-3">{translate text="Provider"}: </label>
		<div class="col-sm-9">
      <select name="provider" id="provider" class="form-control">
        <option selected="selected" value="">{translate text="Select your carrier"}</option>
        {foreach from=$carriers key=val item=details}
        <option value="{$val}">{$details.name|escape}</option>
        {/foreach}
      </select>
    </div>
  </div>
</form>
