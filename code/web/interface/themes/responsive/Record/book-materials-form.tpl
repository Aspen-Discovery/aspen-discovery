{strip}
	{if $errorMessage}
		<div class="alert alert-danger">{$errorMessage}</div>
	{/if}
	<form{* name="placeHoldForm"*} id="bookMaterialForm">
	{* TODO: the fallback POST action of form is not implemented *}
		<input type='hidden' name='module' id='module' value='{$activeRecordProfileModule}' />
		<input type="hidden" name="id" value="{$id|replace:'ils:':''}">
	<fieldset>
		<div class="row">
			{*<div class="form-group col-sm-5">*}
			<div class="form-group col-sm-6 col-sm-offset-3">
				<label for="startDate" class="control-label">Start Date</label>
				<div class="input-group">
					<input id="startDate" name="startDate" type="text" class="form-control required" placeholder="mm/dd/yyyy"
					       {if $smarty.request.startDate} value="{$smarty.request.startDate}" {/if}
					       data-provide="datepicker" data-date-format="mm/dd/yyyy" data-date-start-date="0d" data-date-end-date="+2y"
					       data-date-autoclose="true" data-disabletouchkeyboard="true" {* TODO: test that does this works on mibile devices*}
									>
					<span class="input-group-addon"><span class="glyphicon glyphicon-calendar" onclick="$('#startDate').datepicker('show')" aria-hidden="true"></span></span>
				</div>
			</div>
			{*<div class="form-group col-sm-5 ui-front">
				<label for="startTime" class="control-label">Start Time</label>
				<input id="startTime" name="startTime" type="text" class="form-control bookingTime required"  placeholder="{$smarty.now|date_format:'%l:%M%p'|lower}"
				       {if $smarty.request.startTime} value="{$smarty.request.startTime}" {/if}
								>
				*}{* the class ui-front ensures the jquery autocomplete attaches to the input's parent, thus ensuring it is displayed within/on top of the modal box*}{*
			</div>*}
		</div>
		<hr>
		<div class="row">
			{*<div class="form-group col-sm-5">*}
			<div class="form-group col-sm-6 col-sm-offset-3">

			<label for="endDate" class="control-label" >End Date</label>
				<div class="input-group input-append date">
					<input id="endDate" name="endDate" type="text" class="form-control required" placeholder="mm/dd/yyyy"
					       {if $smarty.request.endDate} value="{$smarty.request.endDate}" {/if}
					       data-provide="datepicker" data-date-format="mm/dd/yyyy" data-date-start-date="0d" data-date-end-date="+2y"
					       data-date-autoclose="true" data-disabletouchkeyboard="true" {* TODO: test that does this works on mibile devices*}
									>
					<span class="input-group-addon"><span class="glyphicon glyphicon-calendar" onclick="$('#endDate').focus().datepicker('show')" aria-hidden="true"></span></span>
				</div>
			</div>
			{*<div class="form-group col-sm-5 ui-front">
				<label for="endTime" class="control-label">End Time</label>
				<input id="endTime" name="endTime" type="text" class="form-control bookingTime required" placeholder="{$smarty.now|date_format:'%l:%M%p'|lower}"
				       {if $smarty.request.endTime} value="{$smarty.request.endTime}" {/if}
								>
			</div>*}
		</div>
	</fieldset>
</form>
	<hr>
	<div class="row">
		<button id="calendarButton" class="btn btn-info center-block" type="button" data-toggle="collapse" data-target="#bookingCalendar" aria-expanded="false" aria-controls="bookingCalendar" style="display: none; margin-bottom: 10px;">
			Show/Hide Hourly Calendar
		</button>

		<div class="col-xs-10 col-xs-offset-1">
			<style type="text/css" scoped>
				#bookingCalendar table td.active {ldelim} {* muted text applied to closed and unavailable times. *}
					color: #999999;
				{rdelim}
			</style>

			<div id="bookingCalendar" class="collapse"></div>
	</div>
	<script type="text/javascript">
		{if !$errorMessage}{* don't add this on reload of form *}
{*		{literal}
		var time = [], hours = [1,2,3,4,5,6,7,8,9,10,11,12], mins = ['00',10,20,30,40,50], meridian = ['pm','am'];
		meridian.forEach(function(ampm){hours.forEach(function(hour){mins.forEach(function(min){time[time.length] = hour + ':' + min + ampm})})});

		jQuery.validator.addMethod("bookingTime", function(value, element) {
							return this.optional(element) || /^([0-9]|1[0-2])\:[0-5][0-9][a,p]m$/.test(value);
						}, "Please enter a valid time"
		);
		{/literal}*}{/if}{literal}

		$(function(){
			$('#bookMaterialForm').validate({
				submitHandler: function(){
					AspenDiscovery.Record.submitBookMaterialForm();
				},
				highlight: function(e){
					$(e).closest('.form-group').addClass('has-error')
				},
				unhighlight: function(e){
					$(e).closest('.form-group').removeClass('has-error')
				}
 			});

			{/literal}{*
				var added = false; // flag for ':' being added on the last key press
//			$('#endTime, #startTime').autocomplete({source:time})
				$('#endTime, #startTime').autocomplete({
					source: function (req, response) {
						response(time.filter(function (t) {
							return new RegExp('^' + req.term, 'i').test(t)
						}))
					}
				})
								.keydown(function (e) {
									if (added && e.which == 8) {
										var input = $(this).val();
										$(this).val(input.substr(0, input.length - 1)); // strip out ':' & and previous character
									}
								})
								.keyup(function () {
									var input = $(this).val();
									added = ( (input.length == 1 && $.isNumeric(input) && input != 1) // typing first digit of the hour, not a 1
									|| (input.length == 2 && $.isNumeric(input))  ); // typing the second digit of the hour, 10 & greater
									if (added) $(this).val(input + ':'); // add ':' after initial numbers typed (treat as hours on 12 hour clock)
								});
			*}{literal}

			$.get(Globals.path + '/{/literal}{$activeRecordProfileModule}/{$id|replace:'ils:':''}{literal}/AJAX?method=getBookingCalendar',
							function(data){
								if (data) {
									$('#bookingCalendar').append(data);
									$('#calendarButton').show(); // show button when we are able to get the calendar
								}
			}, 'html');
		});

		/* TODO: not in ready block, is that a mistake? */
		{/literal}{if !$errorMessage}{* initial load only (the error message will be populated on subsequent loads)
		The section of code causes "too much recursion" errors on the second load *}{literal}
		$('#startDate').on('changeDate', function(e){
			if (!$('#endDate').datepicker('getDate')) $('#endDate').datepicker('setStartDate', $(this).datepicker('getDate'))
		});
		$('#endDate').on('changeDate', function(e){
			if (!$('#startDate').datepicker('getDate')) $('#startDate').datepicker('setEndDate', $(this).datepicker('getDate'))
		});

		{/literal}{/if}
		{* time is an array of valid times to chose from, by 10 minutes intervals.

		   the validator test is specific to booking times

		  the autocomplete source uses a custom searching function that matches the term against the start of the valid times.
		  so typing 3, will return all the times with an hour of 3
		 *}

	</script>
{/strip}
{*
<script type="text/javascript">
	{literal}
	var hours = [1,2,3,4,5,6,7,8,9,10,11,12],
					mins = [10,20,30,40,50],
					meridian = ['am','pm'],
					time = [];

	meridian.forEach(function(ampm){
		hours.forEach(function(hour){
			mins.forEach(function(min){
				time[time.length] = hour + ':' + min + ampm
			})
		})
	});

	$(function(){
		$('#bookMaterialForm').validate();
		var hours
	})
	{/literal}
</script> *}