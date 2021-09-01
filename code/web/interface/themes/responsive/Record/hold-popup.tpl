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
			{if $volume}
				<input type="hidden" name="volume" id="volume" value="{$volume}">
			{/if}
			<fieldset>
				<div class="holdsSummary">
					<input type="hidden" name="holdCount" id="holdCount" value="1">
					<div class="alert alert-warning" id="overHoldCountWarning" {if !$showOverHoldLimit}style="display:none"{/if}>
						{translate text="Warning: You have reached the maximum of <span class=\"maxHolds\">%1%</span> holds for your account.  You must cancel a hold before you can place a hold on this title." 1=$maxHolds isPublicFacing=true}
					</div>
					<div id="holdError" class="pageWarning" style="display: none"></div>
				</div>

				<p class="alert alert-info">
					{if $mustPickupAtHoldingBranch}
						{translate text="Holds allow you to request that a title be put aside for you to pick up at the library." isPublicFacing=true}&nbsp;
					{else}
						{translate text="Holds allow you to request that a title be delivered to your home library." isPublicFacing=true}&nbsp;
					{/if}
					{if $showDetailedHoldNoticeInformation && $profile->_noticePreferenceLabel == 'Mail' && !$treatPrintNoticesAsPhoneNotices}
						{translate text="Once the title arrives at your library you will be mailed a notification informing you that the title is ready for you." isPublicFacing=true}&nbsp;
					{elseif $showDetailedHoldNoticeInformation && ($profile->_noticePreferenceLabel == 'Telephone' || ($profile->_noticePreferenceLabel eq 'Mail' && $treatPrintNoticesAsPhoneNotices))}
						{translate text="Once the title arrives at your library you will receive a phone call informing you that the title is ready for you." isPublicFacing=true}&nbsp;
					{elseif $showDetailedHoldNoticeInformation && $profile->_noticePreferenceLabel == 'Email'}
						{translate text="Once the title arrives at your library you will be emailed a notification informing you that the title is ready for you." isPublicFacing=true}&nbsp;
					{else}
						{translate text="Once the title arrives at your library you will receive a notification informing you that the title is ready for you." isPublicFacing=true}&nbsp;
					{/if}
					{if $mustPickupAtHoldingBranch}
						{translate text="You will then have 7 days to pick up the title at the library." isPublicFacing=true}&nbsp;
					{else}
						{translate text="You will then have 7 days to pick up the title from your home library." isPublicFacing=true}&nbsp;
					{/if}
				</p>

				<div id="holdOptions">
					{assign var="onlyOnePickupLocation" value=false}
					{if count($pickupLocations) == 1}
						{foreach from=$pickupLocations item=firstLocation}
							{if !is_string($firstLocation) && ($firstLocation->code == $user->getPickupLocationCode())}
								{assign var="onlyOnePickupLocation" value=true}
							{/if}
						{/foreach}
					{/if}
					{if ($rememberHoldPickupLocation && $allowRememberPickupLocation) || $onlyOnePickupLocation }
						<input type="hidden" name="pickupBranch" id="pickupBranch" value="{$user->getPickupLocationCode()}">
						{if ($rememberHoldPickupLocation && $allowRememberPickupLocation)}
							<input type="hidden" name="rememberHoldPickupLocation" id="rememberHoldPickupLocation" value="true">
						{else}
							<input type="hidden" name="rememberHoldPickupLocation" id="rememberHoldPickupLocation" value="off">
						{/if}
						<input type="hidden" name="user" id="user" value="{$user->id}">
					{else}
						<div id="pickupLocationOptions" class="form-group">
							<label class="control-label" for="pickupBranch">{translate text="I want to pick this up at" isPublicFacing=true} </label>
							<div class="controls">
								<select name="pickupBranch" id="pickupBranch" class="form-control">
									{if count($pickupLocations) > 0}
										{foreach from=$pickupLocations item=location}
											{if is_string($location)}
												<option value="undefined">{$location}</option>
											{else}
												<option value="{$location->code}" data-users="[{$location->pickupUsers|@implode:','}]" {if $location->code == $user->getPickupLocationCode()}selected{/if}>{$location->displayName}</option>
											{/if}
										{/foreach}
									{else}
										<option>placeholder</option>
									{/if}
								</select>

								{if !$multipleUsers && $allowRememberPickupLocation}
									<div class="form-group">
										<label for="rememberHoldPickupLocation" class="checkbox"><input type="checkbox" name="rememberHoldPickupLocation" id="rememberHoldPickupLocation"> {translate text="Always use this pickup location" isPublicFacing=true}</label>
									</div>
								{else}
									<input type="hidden" name="rememberHoldPickupLocation"  id="rememberHoldPickupLocation" value="off">
								{/if}
							</div>
						</div>

						<div id="userOption" class="form-group"{if !$multipleUsers} style="display: none"{/if}>{* display if there are multiple accounts *}
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
								{$activeUserId}: "{$userDisplayName|escape:javascript} - {$user->getHomeLibrarySystemName()}",
								{assign var="linkedUsers" value=$user->getLinkedUsers()}
								{foreach from="$linkedUsers" item="linkedUser"}
								{$linkedUser->id}: "{$linkedUser->displayName|escape:javascript} - {$linkedUser->getHomeLibrarySystemName()}",
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
								<div class="col-tn-6">
									<label for="holdTypeBib"><input type="radio" name="holdType" value="bib" id="holdTypeBib" checked onchange="$('#itemSelection').hide()"> {translate text="First Available Item" isPublicFacing=true}</label>
								</div>
								<div class="col-tn-6">
									<label for="holdTypeItem"><input type="radio" name="holdType" value="item" id="holdTypeItem" onchange="$('#itemSelection').show()"> {translate text="Specific Item" isPublicFacing=true}</label>
								</div>
							</div>
						{else}
							<input type="hidden" name="holdType" id="holdType" value="item"/>
						{/if}
						<div id="itemSelection" class="form-group" {if $holdType=='either'}style="display: none"{/if}>
							<select name="selectedItem" id="selectedItem" class="form-control" aria-label="{translate text="Selected Item" isPublicFacing=true}">
								{foreach from=$items item=item}
									{if $item.holdable}
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
							<label class="control-label" for="cancelDate">{translate text="Automatically cancel this hold if not filled by" isPublicFacing=true}</label>
							<input type="date" name="cancelDate" id="cancelDate" placeholder="mm/dd/yyyy" class="form-control" size="10" min="{$smarty.now|date_format:"%Y-%m-%d"}">
							<div class="loginFormRow">
								<i>{translate text="If this date is reached, the hold will automatically be cancelled for you.	This is a great way to handle time sensitive materials for term papers, etc. If not set, the cancel date will automatically be set 6 months from today." isPublicFacing=true}</i>
							</div>
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
					<div class="form-group">
						<label for="autologout" class="checkbox"><input type="checkbox" name="autologout" id="autologout" {if $isOpac == true}checked="checked"{/if}> {translate text="Log me out after requesting the item." isPublicFacing=true}</label>
					</div>
				</div>
			</fieldset>
		</form>
	{/if}
</div>
{/strip}