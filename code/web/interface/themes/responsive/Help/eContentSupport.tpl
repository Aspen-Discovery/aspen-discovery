<div class="col-xs-12">
	<div class="alert alert-info">
		Need help downloading a title or using the title on your device?  Please fill out this support form.
	</div>
	<form id="eContentSupport" action="{$path}/Help/eContentSupport" method="post">
		<input type="hidden" name="submit" value="submitted"/>
		{if !$loggedIn}
			<div class='form-group'>
				<label for='libraryCardNumber' class='control-label'>Library Card Number:  <span class="requiredIndicator">*</span></label><input type="text" name="libraryCardNumber" id="libraryCardNumber" class="required form-control" maxlength="20" size="20"/>
			</div>
		{/if}
		<div class='form-group'>
			<label for='name' class='control-label'>Name: <span class="requiredIndicator">*</span></label><input type="text" name="name" id="name" class="required form-control" maxlength="120" size="60" value="{$name}"/>
		</div>
		<div class='form-group'>
			<label for='email' class='control-label'>E-mail: <span class="requiredIndicator">*</span></label><input type="text" name="email" id="email" class="required email form-control" maxlength="120" size="60" value="{$email}"/>
		</div>
		<div class='form-group'>
			<label for='bookAuthor' class='control-label'>Book Title/Author:</label><input type="text" name="bookAuthor" id="bookAuthor" maxlength="120" size="60" class="form-control"/>
		</div>
		<div class='form-group'>
			<label for='device' class='control-label'>Device:</label><input type="text" name="device" id="device" maxlength="120" size="60" class="form-control"/>
		</div>
		<div class='form-group'>
			<label for='format' class='control-label'>Format:</label>
			<select id="format" name="format" class="form-control">
				<option value="na">-Select a Format-</option>
				<option value="ePub">Adobe E-pub eBook</option>
				<option value="kindle">Kindle eBook</option>
				<option value="mp3">MP3 Audio Book</option>
				<option value="wma">WMA Audio Book/Music</option>
				<option value="wmv">WMV Video File</option>
				<option value="Unknown">N/A or Unknown</option>
			</select>
		</div>
		<div class='form-group'>
			<label for='operatingSystem' class='control-label'>Operating System:</label>
			<select name="operatingSystem" id="operatingSystem" class="form-control">
				<option value="">-Select an Operating System-</option>
				<option value="Win-10">Windows 10</option>
				<option value="XP">Windows XP</option>
				<option value="Vista">Windows Vista</option>
				<option value="Win-8">Windows 8</option>
				<option value="Win-7">Windows 7</option>
				<option value="Mac">Max OS X 10.?</option>
				<option value="kindle">Kindle</option>
				<option value="Linux">Linux/Unix</option>
				<option value="Android">Android</option>
				<option value="IOS">iPhone/iPad/iPod</option>
				<option value="other">Other - Please specify Below</option>
			</select>
		</div>
		<div class='form-group'>
			<label for='problem' class='control-label'>Please describe your issue:</label><br/>
			<textarea rows="10" cols="40" name="problem" id="problem" class="form-control"></textarea>
		</div>
		{if $lightbox == false}
			<div class='form-group'>
				<button class='btn btn-sm btn-primary' onclick='return VuFind.EContent.submitHelpForm();'>Submit</button>
			</div>
		{/if}
	</form>
</div>
{literal}
<script type="text/javascript">
$(document).ready(function(){
	var supportForm = $("#eContentSupport");
	supportForm.validate();
});
</script>
{/literal}