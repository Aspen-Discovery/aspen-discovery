{strip}
	<div id="main-content" class="col-xs-12">
		<h1>{translate text="Submit Support Ticket"}</h1>
		<hr>
		{if !empty($error)}
			<div class="alert alert-warning">
				{$error}
			</div>
		{/if}
		<form class="form-horizontal" id="submitTicketForm">
			<div class="form-group">
				<label class="control-label" for="name">{translate text="Your Name"} <span class="required-input">*</span></label>
				<input type="text" class="form-control required" name="name" id="name" value="{$name}">
			</div>
			<div class="form-group">
				<label class="control-label" for="email">{translate text="Email"} <span class="required-input">*</span></label>
				<input type="email" class="form-control required" name="email" id="email" value="{$email}">
			</div>
			<div class="form-group">
				<label class="control-label" for="subject">{translate text="Subject"} <span class="required-input">*</span></label>
				<input type="text" class="form-control required" name="subject" id="subject">
			</div>
			<div class="form-group">
				<label class="control-label" for="description">{translate text="Description"} <span class="required-input">*</span></label>
				<textarea class="form-control required" name="description" id="description"></textarea>
			</div>
			<div class="form-group">
				<label class="control-label" for="criticality">{translate text="Criticality"}</label>
				<select class="form-control" name="criticality" id="criticality">
					<option value="">None Specified</option>
					<option value="Notice Delivery">Notice Delivery</option>
					<option value="Time Sensitive">Time Sensitive</option>
					<option value="Urgent!">Urgent!</option>
				</select>
			</div>
			<div class="form-group">
				<div class="col-xs-12">
					<label class="control-label" for="component">{translate text="Component(s)"}</label>
				</div>
				<div class="checkbox col-xs-12 col-sm-6 col-md-4 col-lg-3">
					<input type="checkbox" name=component[]" value="Account Integration" id="Account Integration"><label for="Account Integration">Account Integration</label>
				</div>
				<div class="checkbox col-xs-12 col-sm-6 col-md-4 col-lg-3">
					<input type="checkbox" name=component[]"  value="Administration" id="Administration"><label for="Administration">Administration</label>
				</div>
				<div class="checkbox col-xs-12 col-sm-6 col-md-4 col-lg-3">
					<input type="checkbox" name=component[]"  value="Aspen API" id="Aspen API"><label for="Aspen API">Aspen API</label>
				</div>
				<div class="checkbox col-xs-12 col-sm-6 col-md-4 col-lg-3">
					<input type="checkbox" name=component[]"  value="Carl.X" id="Carl.X"><label for="Carl.X">Carl.X</label>
				</div>
				<div class="checkbox col-xs-12 col-sm-6 col-md-4 col-lg-3">
					<input type="checkbox" name=component[]"  value="CloudLibrary" id="CloudLibrary"><label for="CloudLibrary">CloudLibrary</label>
				</div>
				<div class="checkbox col-xs-12 col-sm-6 col-md-4 col-lg-3">
					<input type="checkbox" name=component[]"  value="ContentCafe" id="ContentCafe"><label for="ContentCafe">ContentCafe</label>
				</div>
				<div class="checkbox col-xs-12 col-sm-6 col-md-4 col-lg-3">
					<input type="checkbox" name=component[]"  value="Digital Archives" id="Digital Archives"><label for="Digital Archives">Digital Archives</label>
				</div>
				<div class="checkbox col-xs-12 col-sm-6 col-md-4 col-lg-3">
					<input type="checkbox" name=component[]"  value="eCommerce" id="eCommerce"><label for="eCommerce">eCommerce</label>
				</div>
				<div class="checkbox col-xs-12 col-sm-6 col-md-4 col-lg-3">
					<input type="checkbox" name=component[]"  value="Events" id="Events"><label for="Events">Events</label>
				</div>
				<div class="checkbox col-xs-12 col-sm-6 col-md-4 col-lg-3">
					<input type="checkbox" name=component[]"  value="GoodReads" id="GoodReads"><label for="GoodReads">GoodReads</label>
				</div>
				<div class="checkbox col-xs-12 col-sm-6 col-md-4 col-lg-3">
					<input type="checkbox" name=component[]"  value="Grouped Works" id="Grouped Works"><label for="Grouped Works">Grouped Works</label>
				</div>
				<div class="checkbox col-xs-12 col-sm-6 col-md-4 col-lg-3">
					<input type="checkbox" name=component[]"  value="Hoopla" id="Hoopla"><label for="Hoopla">Hoopla</label>
				</div>
				<div class="checkbox col-xs-12 col-sm-6 col-md-4 col-lg-3">
					<input type="checkbox" name=component[]"  value="Horizon" id="Horizon"><label for="Horizon">Horizon</label>
				</div>
				<div class="checkbox col-xs-12 col-sm-6 col-md-4 col-lg-3">
					<input type="checkbox" name=component[]"  value="ILL" id="ILL"><label for="ILL">ILL</label>
				</div>
				<div class="checkbox col-xs-12 col-sm-6 col-md-4 col-lg-3">
					<input type="checkbox" name=component[]"  value="Koha" id="Koha"><label for="Koha">Koha</label>
				</div>
				<div class="checkbox col-xs-12 col-sm-6 col-md-4 col-lg-3">
					<input type="checkbox" name=component[]"  value="Languages &amp; Translations" id="Languages &amp; Translations"><label for="Languages &amp; Translations">Languages &amp; Translations</label>
				</div>
				<div class="checkbox col-xs-12 col-sm-6 col-md-4 col-lg-3">
					<input type="checkbox" name=component[]"  value="Materials Request" id="Materials Request"><label for="Materials Request">Materials Request</label>
				</div>
				<div class="checkbox col-xs-12 col-sm-6 col-md-4 col-lg-3">
					<input type="checkbox" name=component[]"  value="Migration" id="Migration"><label for="Migration">Migration</label>
				</div>
				<div class="checkbox col-xs-12 col-sm-6 col-md-4 col-lg-3">
					<input type="checkbox" name=component[]"  value="New York Times" id="New York Times"><label for="New York Times">New York Times</label>
				</div>
				<div class="checkbox col-xs-12 col-sm-6 col-md-4 col-lg-3">
					<input type="checkbox" name=component[]"  value="Novelist" id="Novelist"><label for="Novelist">Novelist</label>
				</div>
				<div class="checkbox col-xs-12 col-sm-6 col-md-4 col-lg-3">
					<input type="checkbox" name=component[]"  value="OverDrive" id="OverDrive"><label for="OverDrive">OverDrive</label>
				</div>
				<div class="checkbox col-xs-12 col-sm-6 col-md-4 col-lg-3">
					<input type="checkbox" name=component[]"  value="RBdigital" id="WRBdigital"><label for="RBdigital">RBdigital</label>
				</div>
				<div class="checkbox col-xs-12 col-sm-6 col-md-4 col-lg-3">
					<input type="checkbox" name=component[]"  value="Reading History" id="Reading History"><label for="Reading History">Reading History</label>
				</div>
				<div class="checkbox col-xs-12 col-sm-6 col-md-4 col-lg-3">
					<input type="checkbox" name=component[]"  value="Recommendations" id="Recommendations"><label for="Recommendations">Recommendations</label>
				</div>
				<div class="checkbox col-xs-12 col-sm-6 col-md-4 col-lg-3">
					<input type="checkbox" name=component[]"  value="Searching" id="Searching"><label for="Searching">Searching</label>
				</div>
				<div class="checkbox col-xs-12 col-sm-6 col-md-4 col-lg-3">
					<input type="checkbox" name=component[]"  value="Server Setup" id="Server Setup"><label for="Server Setup">Server Setup</label>
				</div>
				<div class="checkbox col-xs-12 col-sm-6 col-md-4 col-lg-3">
					<input type="checkbox" name=component[]"  value="Sierra" id="Sierra"><label for="Sierra">Sierra</label>
				</div>
				<div class="checkbox col-xs-12 col-sm-6 col-md-4 col-lg-3">
					<input type="checkbox" name=component[]"  value="Symphony" id="Symphony"><label for="Symphony">Symphony</label>
				</div>
				<div class="checkbox col-xs-12 col-sm-6 col-md-4 col-lg-3">
					<input type="checkbox" name=component[]"  value="Syndetics" id="Syndetics"><label for="Syndetics">Syndetics</label>
				</div>
				<div class="checkbox col-xs-12 col-sm-6 col-md-4 col-lg-3">
					<input type="checkbox" name=component[]"  value="User Lists" id="User Lists"><label for="User Lists">User Lists</label>
				</div>
				<div class="checkbox col-xs-12 col-sm-6 col-md-4 col-lg-3">
					<input type="checkbox" name=component[]"  value="Website Indexing" id="Website Indexing"><label for="Website Indexing">Website Indexing</label>
				</div>
			</div>
			<div class="form-group">
				<div class="col-xs-12">
					<button type="submit" name="submitTicket" class="btn btn-primary">{translate text="Submit Ticket"}</button>
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