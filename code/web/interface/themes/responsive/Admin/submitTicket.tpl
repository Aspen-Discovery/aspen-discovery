{strip}
	<div id="main-content" class="col-xs-12">
		<h1>{translate text="Submit Support Ticket" isAdminFacing=true}</h1>
		<hr>
		{if !empty($error)}
			<div class="alert alert-warning">
				{$error}
			</div>
		{/if}
		<form id="submitTicketForm" method="post" enctype="multipart/form-data">
			<div class="form-group">
				<label class="control-label" for="name">{translate text="Your Name" isAdminFacing=true} <span class="label label-danger" style="margin-right: .5em;">{translate text="Required" isAdminFacing=true}</span></label>
				<input type="text" class="form-control required" name="name" id="name" value="{$name}">
			</div>
			<div class="form-group">
				<label class="control-label" for="email">{translate text="Email" isAdminFacing=true} <span class="label label-danger" style="margin-right: .5em;">{translate text="Required" isAdminFacing=true}</span></label>
				<input type="email" class="form-control required" name="email" id="email" value="{$email}">
			</div>
			<div class="form-group">
				<label class="control-label" for="subject">{translate text="Subject" isAdminFacing=true} <span class="label label-danger" style="margin-right: .5em;">{translate text="Required" isAdminFacing=true}</span></label>
				<span class="help-block" style="margin-bottom:.5em; margin-top: 0">{translate text="Please use descriptive keywords, i.e. Creating lists in LiDA" isAdminFacing=true}</span>
				<input type="text" class="form-control required" name="subject" id="subject" {if !empty($subject)}value="{$subject}"{/if}>
			</div>
			<div class="form-group">
				<label class="control-label" for="reason">{translate text="Reason" isAdminFacing=true}</label>
				<select class="form-control" name="reason" id="reason">
					<option value="Something is not appearing or working as expected" {if !empty($reason) && $reason == 'Something is not appearing or working as expected'}selected{/if}>{translate text="Something is not appearing or working as expected" isAdminFacing=true inAttribute=true}</option>
					<option value="Question about setting or workflow" {if !empty($reason) && $reason == 'Question about setting or workflow'}selected{/if}>{translate text="Question about setting or workflow" isAdminFacing=true inAttribute=true}</option>
					<option value="Request for a new feature or integration" {if !empty($reason) && $reason == 'Request for a new feature or integration'}selected{/if}>{translate text="Request for a new feature or integration" isAdminFacing=true inAttribute=true}</option>
					<option value="Other" {if !empty($reason) && $reason == 'Other'}selected{/if}>{translate text="Other" isAdminFacing=true inAttribute=true}</option>
				</select>
			</div>
			<div class="form-group">
				<label class="control-label" for="product">{translate text="Product" isAdminFacing=true}</label>
				<select class="form-control" name="product" id="product">
					<option value="Aspen Discovery Only" {if !empty($product) && $product == 'Aspen Discovery Only'}selected{/if}>{translate text="Aspen Discovery Only" isAdminFacing=true inAttribute=true}</option>
					<option value="Aspen LiDA Only" {if !empty($product) && $product == 'Aspen LiDA Only'}selected{/if}>{translate text="Aspen LiDA Only" isAdminFacing=true inAttribute=true}</option>
					<option value="Both Aspen Discovery and Aspen LiDA" {if !empty($product) && $product == 'Both Aspen Discovery and Aspen LiDA'}selected{/if}>{translate text="Both Aspen Discovery and Aspen LiDA" isAdminFacing=true inAttribute=true}</option>
				</select>
			</div>
			<div class="form-group">
				<label class="control-label" for="description">{translate text="Description" isAdminFacing=true} <span class="label label-danger" style="margin-right: .5em;">{translate text="Required" isAdminFacing=true}</span></label>
				<span class="help-block" style="margin-bottom:.5em; margin-top: 0">{translate text="Include detailed steps to replicate the problem, account number for impacted user(s), device information, etc. Please do not include sensitive information like login credentials." isAdminFacing=true}</span>
				<textarea class="form-control required" name="description" id="description">{if !empty($description)}{$description}{/if}</textarea>
			</div>
			{if $supportingCompany == 'ByWater Solutions'}
			<div class="form-group">
				<label class="control-label" for="sharepass">{translate text="Sharepass Url" isAdminFacing=true}</label>
				<span class="help-block" style="margin-bottom:.5em; margin-top: 0">{translate text="Use Sharepass to send sensitive information to us such as login credentials we can use to replicate the behavior reported." isAdminFacing=true} <a href="https://app.tango.us/app/workflow/Share-sensitive-information-with-ByWater-SharePass-ea39fbda64f24949a1a8a80a1be8223b" target="_blank"><u>{translate text="Learn how to use Sharepass." isAdminFacing=true}</u></a></span>
				<input type="url" class="form-control" name="sharepass" placeholder="https://" id="sharepass" {if !empty($sharepass)}value="{$sharepass}"{/if}>
			</div>
            {/if}
			<div class="form-group">
				<label class="control-label" id="examples">{translate text="Url(s) to example records" isAdminFacing=true}</label>
				<fieldset>
					<input style="margin-bottom: .5em" aria-labelledby="examples" type="url" class="form-control" placeholder="http://" name="example1" id="example1" {if !empty($example1)}value="{$example1}"{/if}>
					<input style="margin-bottom: .5em" aria-labelledby="examples" type="url" class="form-control" placeholder="http://" name="example2" id="example2" {if !empty($example2)}value="{$example2}"{/if}>
					<input aria-labelledby="examples" type="url" class="form-control" placeholder="http://" name="example3" id="example3" {if !empty($example3)}value="{$example3}"{/if}>
				</fieldset>
			</div>
			<div class="form-group">
				<label class="control-label" for="attachments">{translate text="Attachment(s)" isAdminFacing=true}</label>
				<span class="help-block" style="margin-bottom:.5em; margin-top: 0">{translate text="Attach any screenshots or recordings that display the reported behavior or to help us replicate it. To select multiple files, hold down the CTRL or SHIFT key while selecting." isAdminFacing=true}</span>
				<input type="file" name="attachments[]" id="attachments" multiple>
				<span class="help-block small">{translate text="10MB size limit per attachment." isAdminFacing=true}</span>
			</div>
			<div class="form-group">
					<button type="submit" name="submitTicket" class="btn btn-primary">{translate text="Submit Ticket" isAdminFacing=true}</button>
			</div>
		</form>
		<script type="application/javascript">
            {literal}
			$("#submitTicketForm").validate();
            {/literal}
		</script>
	</div>
{/strip}