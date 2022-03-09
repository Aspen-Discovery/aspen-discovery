{strip}
	<div id="main-content" class="col-xs-12">
		<h1>{translate text="Submit Support Ticket" isAdminFacing=true}</h1>
		<hr>
		{if !empty($error)}
			<div class="alert alert-warning">
				{$error}
			</div>
		{/if}
		<form class="form-horizontal" id="submitTicketForm">
			<div class="form-group">
				<label class="control-label" for="name">{translate text="Your Name" isAdminFacing=true} <span class="required-input">*</span></label>
				<input type="text" class="form-control required" name="name" id="name" value="{$name}">
			</div>
			<div class="form-group">
				<label class="control-label" for="email">{translate text="Email" isAdminFacing=true} <span class="required-input">*</span></label>
				<input type="email" class="form-control required" name="email" id="email" value="{$email}">
			</div>
			<div class="form-group">
				<label class="control-label" for="subject">{translate text="Subject" isAdminFacing=true} <span class="required-input">*</span></label>
				<input type="text" class="form-control required" name="subject" id="subject">
			</div>
			<div class="form-group">
				<label class="control-label" for="description">{translate text="Description" isAdminFacing=true} <span class="required-input">*</span></label>
				<textarea class="form-control required" name="description" id="description"></textarea>
			</div>
			<div class="form-group">
				<label class="control-label" for="criticality">{translate text="Criticality" isAdminFacing=true}</label>
				<select class="form-control" name="criticality" id="criticality">
					<option value="">{translate text="None Specified" isAdminFacing=true inAttribute=true}</option>
					<option value="Notice Delivery">{translate text="Notice Delivery" isAdminFacing=true inAttribute=true}</option>
					<option value="Time Sensitive">{translate text="Time Sensitive" isAdminFacing=true inAttribute=true}</option>
					<option value="Urgent!">{translate text="Urgent!" isAdminFacing=true inAttribute=true}</option>
				</select>
			</div>
			<div class="form-group">
				<div class="col-xs-12">
					<label class="control-label" for="component">{translate text="Component(s)" isAdminFacing=true}</label>
				</div>
				<div class="checkbox col-xs-12 col-sm-6 col-md-4 col-lg-3">
					<input type="checkbox" name=component[]" value="Account Integration" id="Account Integration"><label for="Account Integration">{translate text="Account Integration" isAdminFacing=true}</label>
				</div>
				<div class="checkbox col-xs-12 col-sm-6 col-md-4 col-lg-3">
					<input type="checkbox" name=component[]"  value="Administration" id="Administration"><label for="Administration">{translate text="Administration" isAdminFacing=true}</label>
				</div>
				<div class="checkbox col-xs-12 col-sm-6 col-md-4 col-lg-3">
					<input type="checkbox" name=component[]"  value="Aspen API" id="Aspen API"><label for="Aspen API">{translate text="Aspen API" isAdminFacing=true}</label>
				</div>
				<div class="checkbox col-xs-12 col-sm-6 col-md-4 col-lg-3">
					<input type="checkbox" name=component[]"  value="Carl.X" id="Carl.X"><label for="Carl.X">{translate text="Carl.X" isAdminFacing=true}</label>
				</div>
				<div class="checkbox col-xs-12 col-sm-6 col-md-4 col-lg-3">
					<input type="checkbox" name=component[]"  value="CloudLibrary" id="CloudLibrary"><label for="CloudLibrary">{translate text="CloudLibrary" isAdminFacing=true}</label>
				</div>
				<div class="checkbox col-xs-12 col-sm-6 col-md-4 col-lg-3">
					<input type="checkbox" name=component[]"  value="ContentCafe" id="ContentCafe"><label for="ContentCafe">{translate text="ContentCafe" isAdminFacing=true}</label>
				</div>
				<div class="checkbox col-xs-12 col-sm-6 col-md-4 col-lg-3">
					<input type="checkbox" name=component[]"  value="Digital Archives" id="Digital Archives"><label for="Digital Archives">{translate text="Digital Archives" isAdminFacing=true}</label>
				</div>
				<div class="checkbox col-xs-12 col-sm-6 col-md-4 col-lg-3">
					<input type="checkbox" name=component[]"  value="eCommerce" id="eCommerce"><label for="eCommerce">{translate text="eCommerce" isAdminFacing=true}</label>
				</div>
				<div class="checkbox col-xs-12 col-sm-6 col-md-4 col-lg-3">
					<input type="checkbox" name=component[]"  value="Events" id="Events"><label for="Events">{translate text="Events" isAdminFacing=true}</label>
				</div>
				<div class="checkbox col-xs-12 col-sm-6 col-md-4 col-lg-3">
					<input type="checkbox" name=component[]"  value="GoodReads" id="GoodReads"><label for="GoodReads">{translate text="GoodReads" isAdminFacing=true}</label>
				</div>
				<div class="checkbox col-xs-12 col-sm-6 col-md-4 col-lg-3">
					<input type="checkbox" name=component[]"  value="Grouped Works" id="Grouped Works"><label for="Grouped Works">{translate text="Grouped Works" isAdminFacing=true}</label>
				</div>
				<div class="checkbox col-xs-12 col-sm-6 col-md-4 col-lg-3">
					<input type="checkbox" name=component[]"  value="Hoopla" id="Hoopla"><label for="Hoopla">{translate text="Hoopla" isAdminFacing=true}</label>
				</div>
				<div class="checkbox col-xs-12 col-sm-6 col-md-4 col-lg-3">
					<input type="checkbox" name=component[]"  value="Horizon" id="Horizon"><label for="Horizon">{translate text="Horizon" isAdminFacing=true}</label>
				</div>
				<div class="checkbox col-xs-12 col-sm-6 col-md-4 col-lg-3">
					<input type="checkbox" name=component[]"  value="ILL" id="ILL"><label for="ILL">{translate text="ILL" isAdminFacing=true}</label>
				</div>
				<div class="checkbox col-xs-12 col-sm-6 col-md-4 col-lg-3">
					<input type="checkbox" name=component[]"  value="Koha" id="Koha"><label for="Koha">{translate text="Koha" isAdminFacing=true}</label>
				</div>
				<div class="checkbox col-xs-12 col-sm-6 col-md-4 col-lg-3">
					<input type="checkbox" name=component[]"  value="Languages &amp; Translations" id="Languages &amp; Translations"><label for="Languages &amp; Translations">{translate text="Languages & Translations" isAdminFacing=true}</label>
				</div>
				<div class="checkbox col-xs-12 col-sm-6 col-md-4 col-lg-3">
					<input type="checkbox" name=component[]"  value="Materials Request" id="Materials Request"><label for="Materials Request">{translate text="Materials Request" isAdminFacing=true}</label>
				</div>
				<div class="checkbox col-xs-12 col-sm-6 col-md-4 col-lg-3">
					<input type="checkbox" name=component[]"  value="Migration" id="Migration"><label for="Migration">{translate text="Migration" isAdminFacing=true}</label>
				</div>
				<div class="checkbox col-xs-12 col-sm-6 col-md-4 col-lg-3">
					<input type="checkbox" name=component[]"  value="New York Times" id="New York Times"><label for="New York Times">{translate text="New York Times" isAdminFacing=true}</label>
				</div>
				<div class="checkbox col-xs-12 col-sm-6 col-md-4 col-lg-3">
					<input type="checkbox" name=component[]"  value="Novelist" id="Novelist"><label for="Novelist">{translate text="Novelist" isAdminFacing=true}</label>
				</div>
				<div class="checkbox col-xs-12 col-sm-6 col-md-4 col-lg-3">
					<input type="checkbox" name=component[]"  value="OverDrive" id="OverDrive"><label for="OverDrive">{translate text="OverDrive" isAdminFacing=true}</label>
				</div>
				<div class="checkbox col-xs-12 col-sm-6 col-md-4 col-lg-3">
					<input type="checkbox" name=component[]"  value="Reading History" id="Reading History"><label for="Reading History">{translate text="Reading History" isAdminFacing=true}</label>
				</div>
				<div class="checkbox col-xs-12 col-sm-6 col-md-4 col-lg-3">
					<input type="checkbox" name=component[]"  value="Recommendations" id="Recommendations"><label for="Recommendations">{translate text="Recommendations" isAdminFacing=true}</label>
				</div>
				<div class="checkbox col-xs-12 col-sm-6 col-md-4 col-lg-3">
					<input type="checkbox" name=component[]"  value="Searching" id="Searching"><label for="Searching">{translate text="Searching" isAdminFacing=true}</label>
				</div>
				<div class="checkbox col-xs-12 col-sm-6 col-md-4 col-lg-3">
					<input type="checkbox" name=component[]"  value="Server Setup" id="Server Setup"><label for="Server Setup">{translate text="Server Setup" isAdminFacing=true}</label>
				</div>
				<div class="checkbox col-xs-12 col-sm-6 col-md-4 col-lg-3">
					<input type="checkbox" name=component[]"  value="Sierra" id="Sierra"><label for="Sierra">{translate text="Sierra" isAdminFacing=true}</label>
				</div>
				<div class="checkbox col-xs-12 col-sm-6 col-md-4 col-lg-3">
					<input type="checkbox" name=component[]"  value="Symphony" id="Symphony"><label for="Symphony">{translate text="Symphony" isAdminFacing=true}</label>
				</div>
				<div class="checkbox col-xs-12 col-sm-6 col-md-4 col-lg-3">
					<input type="checkbox" name=component[]"  value="Syndetics" id="Syndetics"><label for="Syndetics">{translate text="Syndetics" isAdminFacing=true}</label>
				</div>
				<div class="checkbox col-xs-12 col-sm-6 col-md-4 col-lg-3">
					<input type="checkbox" name=component[]"  value="User Lists" id="User Lists"><label for="User Lists">{translate text="User Lists" isAdminFacing=true}</label>
				</div>
				<div class="checkbox col-xs-12 col-sm-6 col-md-4 col-lg-3">
					<input type="checkbox" name=component[]"  value="Web Builder" id="Web Builder"><label for="Web Builder">{translate text="Web Builder" isAdminFacing=true}</label>
				</div>
				<div class="checkbox col-xs-12 col-sm-6 col-md-4 col-lg-3">
					<input type="checkbox" name=component[]"  value="Website Indexing" id="Website Indexing"><label for="Website Indexing">{translate text="Website Indexing" isAdminFacing=true}</label>
				</div>
			</div>
			<div class="form-group">
				<div class="col-xs-12">
					<button type="submit" name="submitTicket" class="btn btn-primary">{translate text="Submit Ticket" isAdminFacing=true}</button>
				</div>
			</div>
		</form>
		<script type="application/javascript">
            {literal}
			$("#submitTicketForm").validate();
            {/literal}
		</script>
	</div>
{/strip}