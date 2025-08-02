{strip}
<div id="page-content" class="content">
	{if $holdType == 'none'}
		<p>
			{translate text="Sorry, this title does not allow holds.  Please visit the library to use it." isPublicFacing=true}
		</p>
	{else}
		<form name="placeHoldForm" id="placeHoldForm" method="post" class="form">
			<input type="hidden" name="id" id="id" value="{$id}">
			<input type="hidden" name="recordSource" id="recordSource" value="{$recordSource}">
			<input type="hidden" name="module" id="module" value="{$activeRecordProfileModule}">
			{if !empty($volume)}
				<input type="hidden" name="volume" id="volume" value="{$volume}">
			{/if}
			{if !empty($variationId)}
				<input type="hidden" name="variationId" id="variationId" value="{$variationId}">
			{/if}
			<fieldset>
				<div class="holdsSummary">
					<input type="hidden" name="holdCount" id="holdCount" value="1">
					<div class="alert alert-warning" id="overHoldCountWarning" {if empty($showOverHoldLimit)}style="display:none"{/if}>
						{translate text="Warning: You have reached the maximum of <span class=\"maxHolds\">%1%</span> holds for your account.  You must cancel a hold before you can place a hold on this title." 1=$maxHolds isPublicFacing=true}
					</div>
					<div id="holdError" class="pageWarning" style="display: none"></div>
				</div>

				{if $isOnHold}
				<p class="alert alert-warning">
					{translate text="This title is already on hold for you. Are you sure you want to place a duplicate hold?" isPublicFacing=true}&nbsp;
				</p>
				{else}
				<p class="alert alert-info">
					{if $pickupAt > 0}
						{translate text="Holds allow you to request that a title be put aside for you to pick up at the library." isPublicFacing=true}&nbsp;
					{else}
						{translate text="Holds allow you to request that a title be delivered to your home library." isPublicFacing=true}&nbsp;
					{/if}
						{translate text="Once the title arrives at your library you will receive a notification informing you that the title is ready for you." isPublicFacing=true}&nbsp;
					{if $pickupAt > 0}
						{translate text="You will then have 7 days to pick up the title at the library." isPublicFacing=true}&nbsp;
					{else}
						{translate text="You will then have 7 days to pick up the title from your home library." isPublicFacing=true}&nbsp;
					{/if}
				</p>
				{/if}

				<div id="holdOptions">
					{assign var="onlyOnePickupLocation" value=false}
					{if count($pickupLocations) == 1}
						{foreach from=$pickupLocations item=firstLocation}
							{if !is_string($firstLocation) && ($firstLocation->code == $user->getPickupLocationCode())}
								{assign var="onlyOnePickupLocation" value=true}
							{/if}
						{/foreach}
					{/if}
					{assign var="onlyOnePickupSublocation" value=false}
{*					{if count($pickupSublocations) == 1}*}
{*						{foreach from=$pickupSublocations item=firstSublocation}*}
{*							{if !is_string($firstSublocation) && ($firstSublocation->code == $user->getPickupSublocationCode())}*}
{*								{assign var="onlyOnePickupSublocation" value=true}*}
{*							{/if}*}
{*						{/foreach}*}
{*					{/if}*}
					{if ($rememberHoldPickupLocation && $allowRememberPickupLocation) || $onlyOnePickupLocation}
						<input type="hidden" name="pickupBranch" id="pickupBranch" value="{$user->getPickupLocationCode()}">
						{if ($rememberHoldPickupLocation && $allowRememberPickupLocation)}
							<input type="hidden" name="rememberHoldPickupLocation" id="rememberHoldPickupLocation" value="true">
						{else}
							<input type="hidden" name="rememberHoldPickupLocation" id="rememberHoldPickupLocation" value="off">
						{/if}

{*						{if $onlyOnePickupSublocation}*}
{*							<input type="hidden" name="pickupSublocation" id="pickupSublocation" value="{$user->getPickupSublocationCode()}">*}
{*						{/if}*}
						<input type="hidden" name="user" id="user" value="{$user->id}">
					{else}
						<div id="pickupLocationOptions" class="form-group">
							<label class="control-label" for="pickupBranch">{translate text="I want to pick this up at" isPublicFacing=true} </label>
							<div class="controls">
								<select name="pickupBranch" id="pickupBranch" class="form-control" onchange="AspenDiscovery.Record.generateSublocationSelect();">
									{if count($pickupLocations) > 0}
										{foreach from=$pickupLocations item=location}
											{if is_string($location)}
												<option value="undefined">{$location}</option>
											{else}
												<option value="{$location->code}" data-users="[{implode subject=$location->getPickupUsers() glue=','}]" {if $location->code == $user->getPickupLocationCode()}selected{/if}>{$location->displayName|escape}</option>
											{/if}
										{/foreach}
									{else}
										<option>placeholder</option>
									{/if}
								</select>

								<div id="pickupSublocationOptions" style="margin-top:15px">
									<div id="sublocationSelectPlaceHolder"></div>
								</div>

								{if empty($multipleUsers) && $allowRememberPickupLocation}
									<div class="form-group">
										<label for="rememberHoldPickupLocation" class="checkbox"><input type="checkbox" name="rememberHoldPickupLocation" id="rememberHoldPickupLocation"> {translate text="Always use this pickup location" isPublicFacing=true}</label>
									</div>
								{else}
									<input type="hidden" name="rememberHoldPickupLocation"  id="rememberHoldPickupLocation" value="off">
								{/if}
							</div>
						</div>

						<div id="userOption" class="form-group"{if empty($multipleUsers)} style="display: none"{/if}>{* display if there are multiple accounts *}
							<label for="user" class="control-label">{translate text="Place hold for the chosen location using account" isPublicFacing=true}</label>
							<div class="controls">
								<select name="user" id="user" class="form-control">
									{* Built by jQuery below *}
								</select>
							</div>
						</div>

						<script type="text/javascript">
							$(function(){ldelim}
								var userNames = {ldelim}
								{$activeUserId}: "{$userDisplayName|escape|escape:javascript} - {$user->getHomeLibrarySystemName()|escape|escape:javascript}",
								{foreach from=$linkedUsers item=linkedUser}
								{$linkedUser->id}: "{$linkedUser->displayName|escape|escape:javascript} - {$linkedUser->getHomeLibrarySystemName()|escape|escape:javascript}",
								{/foreach}
								{rdelim};
								$('#pickupBranch').change(function(){ldelim}
									var users = $('option:selected', this).data('users');
									var options = '';
									if (typeof(users) !== "undefined") {ldelim}
										$.each(users, function (indexIgnored, userId) {ldelim}
											options += '<option value="' + userId + '">' + userNames[userId] + '</option>';
										{rdelim});
									{rdelim}
									$('#userOption select').html(options);
								{rdelim}).change(); /* trigger on initial load */
							{rdelim});
						</script>
					{/if}

					{if $holdType == 'either' || $holdType == 'item'}
						<label class="control-label">{translate text="Place hold on" isPublicFacing=true}</label>
						{if $holdType == 'either'}
							<div id="holdTypeSelection" class="form-group">
								<div class="row">
									<div class="col-tn-6">
										<label for="holdTypeBib"><input type="radio" name="holdType" value="bib" id="holdTypeBib" checked onchange="$('#itemSelection').hide()"> {translate text="First Available Item" isPublicFacing=true}</label>
									</div>
									<div class="col-tn-6">
										<label for="holdTypeItem"><input type="radio" name="holdType" value="item" id="holdTypeItem" onchange="$('#itemSelection').show()"> {translate text="Specific Item" isPublicFacing=true}</label>
									</div>
								</div>
							</div>
						{else}
							<input type="hidden" name="holdType" id="holdType" value="item"/>
						{/if}
						<div id="itemSelection" class="form-group" {if $holdType=='either'}style="display: none"{/if}>
							<select name="selectedItem" id="selectedItem" class="form-control" aria-label="{translate text="Selected Item" isPublicFacing=true}">
								<option value="">{translate text="Please select an item from the list below" isPublicFacing=true}</option>
								{foreach from=$items item=item}
									{if !empty($item.holdable)}
										<option value="{$item.itemId}">{$item.description}</option>
									{/if}
								{/foreach}
							</select>
						</div>
					{else}
						<input type="hidden" name="holdType" id="holdType" value="{$holdType}"/>
					{/if}

					{if $showHoldCancelDate == 1}
						<div id="cancelHoldDate" class="form-group">
							{* Determine default cancellation date based on library settings. *}
							{assign var='__prepopDays' value=0}
							{if $defaultNotNeededAfterDays > 0}
								{assign var='__prepopDays' value=$defaultNotNeededAfterDays}
								{if $maxHoldCancellationDate > 0 && $defaultNotNeededAfterDays > $maxHoldCancellationDate}
									{assign var='__prepopDays' value=$maxHoldCancellationDate}
								{/if}
							{/if}
							<label class="control-label" for="cancelDate">{translate text="Automatically cancel this hold if not filled by" isPublicFacing=true}</label>
							<input type="date" name="cancelDate" id="cancelDate" placeholder="mm/dd/yyyy" class="form-control" size="10"{if $__prepopDays > 0} value="{($smarty.now + ($__prepopDays * 24 * 60 * 60))|date_format:"%Y-%m-%d"}"{/if} min="{$smarty.now|date_format:"%Y-%m-%d"}"{if $maxHoldCancellationDate > 0} max="{($smarty.now + ($maxHoldCancellationDate * 24 * 60 * 60))|date_format:"%Y-%m-%d"}"{/if}>
							<div class="help-block" style="margin-top: 4px;">
								<small style="color: #666;">
									<i class="fa fa-info-circle" style="margin-right: 4px;"></i>
									{translate text="If this date is reached, the hold will automatically be cancelled for you." isPublicFacing=true}
									{if $maxHoldCancellationDate > 0}
										{translate text="You can select a cancellation date up to %1% days from today." 1=$maxHoldCancellationDate isPublicFacing=true}
									{/if}
								</small>
							</div>
						</div>
					{/if}
					{if !empty($promptForHoldNotifications)}
						<div id="holdNotification" class="form-group">
							{include file=$holdNotificationTemplate}
						</div>
					{/if}
					{if count($holdDisclaimers) > 0}
						{foreach from=$holdDisclaimers item=holdDisclaimer key=library}
							<div class="holdDisclaimer alert alert-warning">
								{if count($holdDisclaimers) > 1}<div class="holdDisclaimerLibrary">{$library}</div>{/if}
								{$holdDisclaimer}
							</div>
						{/foreach}
					{/if}
					<br>
					{if $showLogMeOut == 1}
					<div class="form-group">
						<label for="autologout" class="checkbox"><input type="checkbox" name="autologout" id="autologout" {if $logMeOutDefault == true}checked="checked"{/if}> {translate text="Log me out after requesting the item." isPublicFacing=true}</label>
					</div>
					{/if}
				</div>
			</fieldset>
		</form>
		<div id="placingHoldMessage" class="alert alert-info" style="display: none">
			{translate text="Placing your hold, this may take a minute." isPublicFacing=true}
		</div>
	{/if}
</div>
{/strip}
