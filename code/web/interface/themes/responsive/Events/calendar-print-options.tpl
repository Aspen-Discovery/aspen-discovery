{strip}
	<input type="hidden" name="print" id="print" value="true">
	<input type="hidden" name="week" id="week" value="{$week}">
	<input type="hidden" name="month" id="month" value="{$month}">
	<input type="hidden" name="year" id="year" value="{$year}">
	<div class="row">
		<div class="col-xs-12">
			<p>{translate text="Select the elements you'd like to print" isPublicFacing=true}</p>
		</div>
		<div class="col-md-6">
			<h4 class="bold">{translate text="Calendar View Options" isPublicFacing=true}</h4>
			<h5>({translate text="Landscape Orientation" isPublicFacing=true})</h5>
			{if !empty($eventFields)}
				{foreach from=$eventFields key=eventFieldId item=eventField}
					<div class="form-group checkbox">
						<label for="calendar_{$eventField.name}">
							<input type="checkbox" class="calendar-print-option" name="calendar_{$eventField.name}" id="calendar_{$eventFieldId}" {if $eventField.printedCalendar}checked{/if}>
							<strong>{$eventField.name}</strong>
						</label>
					</div>
				{/foreach}
			{/if}
			<div class="form-group checkbox">
				<label for="endTime">
					<input type="checkbox" name="endTime" id="endTime">
					<strong>{translate text="End Time" isPublicFacing=true}</strong>
					<span id="endTimeHelpBlock" class="help-block" style="margin-top:0">
						<small><i class="fas fa-info-circle"></i>
							{translate text="If unchecked, end time may still show when there is space." isPublicFacing=true}
						</small>
					</span>
				</label>
			</div>
		</div>
		<div class="col-md-6">
			<h4 class="bold">{translate text="Agenda View Options" isPublicFacing=true}</h4>
			<h5>({translate text="Portrait Orientation" isPublicFacing=true})</h5>
			{if !empty($eventFields)}
				{foreach from=$eventFields key=eventFieldId item=eventField}
					<div class="form-group checkbox">
						<label for="agenda_{$eventField.name}">
							<input class="agenda-print-option" type="checkbox" name="agenda_{$eventField.name}" id="agenda_{$eventFieldId}" {if $eventField.printedAgenda}checked{/if}>
							<strong>{$eventField.name}</strong>
						</label>
					</div>
				{/foreach}
			{/if}
		</div>
	</div>
{/strip}
