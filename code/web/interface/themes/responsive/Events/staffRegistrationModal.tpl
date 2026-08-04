{strip}
<div id="staffRegistrationModal">
	<input type="hidden" id="staffRegEventInstanceId" value="{$eventInstanceId}">

	<div class="well well-sm">
		<h4>{$eventTitle|escape}</h4>
		<p>
			<strong>{translate text="Date" isAdminFacing=true}:</strong> {$eventDate|date_format:"%B %e, %Y"} {$eventTime}<br>
			{include file="Events/capacityDisplay.tpl" compact=true}
		</p>
	</div>

	<div class="form-group">
		<label for="patronBarcodeInput" class="control-label">{translate text="Patron Barcode" isAdminFacing=true}</label>
		<div class="input-group">
			<input type="text" id="patronBarcodeInput" class="form-control" placeholder="{translate text='Enter patron barcode' isAdminFacing=true inAttribute=true}">
			<span class="input-group-btn">
				<button type="button" class="btn btn-primary" onclick="AspenDiscovery.Events.lookupPatronForRegistration();">
					<i class="fas fa-search"></i> {translate text="Lookup" isAdminFacing=true}
				</button>
			</span>
		</div>
	</div>

	<div id="patronLookupResult" style="display: none;">
		<div class="well well-sm">
			<h5>{translate text="Patron Found" isAdminFacing=true}</h5>
			<input type="hidden" id="foundPatronId" value="">
			<p>
				<strong>{translate text="Name" isAdminFacing=true}:</strong> <span id="foundPatronName"></span><br>
				<strong>{translate text="Barcode" isAdminFacing=true}:</strong> <span id="foundPatronBarcode"></span><br>
				<strong>{translate text="Email" isAdminFacing=true}:</strong> <span id="foundPatronEmail"></span><br>
				<strong>{translate text="Home Location" isAdminFacing=true}:</strong> <span id="foundPatronLocation"></span>
			</p>
			{include file="AspenEvents/attendeeCategories.tpl"}
			<button type="button" class="btn btn-success" onclick="AspenDiscovery.Events.confirmStaffRegistration();">
				<i class="fas fa-user-plus"></i> {translate text="Register This Patron" isAdminFacing=true}
			</button>
		</div>
	</div>

	<div class="alert alert-info" role="info" aria-live="polite">
		<div class="admin-message-text">
			<p>Registering a patron to an event will add a 'Saved Event' to their account, allowing them to manage their registration.</p>
		</div>
	</div>

	<div id="patronLookupError" class="alert alert-danger" style="display: none;"></div>
</div>
{/strip}
