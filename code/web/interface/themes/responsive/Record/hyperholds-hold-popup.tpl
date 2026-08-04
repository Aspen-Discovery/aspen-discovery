<div class="hyperhold-confirmation">
	<h4>{translate text='Select which editions to include in your Hyperhold' isPublicFacing=true}</h4>
	
	{* Pickup Location Selection *}
	<div class="form-group">
		<label class="control-label" for="pickupBranch">{translate text="I want to pick this up at" isPublicFacing=true}</label>
		<select name="pickupBranch" id="hyperholdPickupBranch" class="form-control">
			{foreach from=$pickupLocations item=location}
				{if is_string($location)}
					<option value="undefined">{$location}</option>
				{else}
					<option value="{$location->code}" {if $location->code == $user->getPickupLocationCode()}selected{/if}>{$location->displayName|escape}</option>
				{/if}
			{/foreach}
		</select>
	</div>
	
	{* Format Selection *}
	{foreach from=$formats item=format}
		<div class="format-block mb-3">
			<label class="format-checkbox">
				<input type="checkbox" 
					   name="hyperholdFormat[]" 
					   value="{$format.name|escape}" 
					   data-format="{$format.name|escape}"
					   checked
					   class="format-selector">
				<strong>{$format.name|escape}</strong> ({$format.recordCount} {if $format.recordCount == 1}edition{else}editions{/if})
			</label>
		
			<button type="button" 
					class="btn btn-sm btn-link" 
					onclick="toggleEditions('{$format.name|escape}')">
				{translate text="Show editions" isPublicFacing=true}
			</button>

			<div id="editions-{$format.name|escape}" class="editions-list ml-4" style="display:none;">
				<ul class="list-unstyled">
					{foreach from=$format.records item=record}
						<li class="mb-2">
							<label>
								<input type="checkbox" 
									   name="hyperholdRecord[]" 
									   value="{$record.id}" 
									   data-format="{$format.name|escape}"
									   checked>
								<strong>{$record.title|escape}</strong>
								{if $record.author} by {$record.author|escape}{/if}
								({$record.copyCount} {if $record.copyCount == 1}copy{else}copies{/if})
							</label>
							
							<button type="button" 
									class="btn btn-xs btn-link" 
									onclick="toggleCopies('{$record.id}')">
								{translate text="Show copies" isPublicFacing=true}
							</button>
							
							<div id="copies-{$record.id}" class="copies-list ml-4" style="display:none;">
								<ul class="list-unstyled small">
									{foreach from=$record.copies item=copy}
										<li>
											{$copy.location|escape} - {$copy.callNumber|escape} ({$copy.status|escape})
										</li>
									{/foreach}
								</ul>
							</div>
						</li>
					{/foreach}
				</ul>
			</div>
		</div>
	{/foreach}
</div>

<script>
function toggleEditions(formatName) {
	var editionsList = document.getElementById('editions-' + formatName);
	var button = event.target;
	
	if (editionsList.style.display === 'none') {
		editionsList.style.display = 'block';
		button.textContent = '{translate text="Hide editions" isPublicFacing=true inAttribute=true}';
	} else {
		editionsList.style.display = 'none';
		button.textContent = '{translate text="Show editions" isPublicFacing=true inAttribute=true}';
	}
}

function toggleCopies(recordId) {
	var copiesList = document.getElementById('copies-' + recordId);
	var button = event.target;
	
	if (copiesList.style.display === 'none') {
		copiesList.style.display = 'block';
		button.textContent = '{translate text="Hide copies" isPublicFacing=true inAttribute=true}';
	} else {
		copiesList.style.display = 'none';
		button.textContent = '{translate text="Show copies" isPublicFacing=true inAttribute=true}';
	}
}

// When format is unchecked, uncheck all its records
document.addEventListener('change', function(e) {
	if (e.target.classList.contains('format-selector')) {
		var formatName = e.target.getAttribute('data-format');
		var records = document.querySelectorAll('input[name="hyperholdRecord[]"][data-format="' + formatName + '"]');
		records.forEach(function(record) {
			record.checked = e.target.checked;
		});
	}
});
</script>