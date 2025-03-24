{strip}
	<div id="aspenEventResult{$resultIndex|escape}" class="resultsList">
		<div class="row">
			{if !empty($showCovers)}
				<div class="coversColumn col-xs-3 col-sm-3 col-md-3 col-lg-2 text-center" aria-hidden="true" role="presentation">
					{if $disableCoverArt != 1}
						<a href="{if $bypassEventPage}{$recordDriver->getExternalUrl()}{else}{$eventUrl}{/if}" class="alignleft listResultImage" onclick="AspenDiscovery.Events.trackUsage('{$id}')" aria-hidden="true">
							<img src="{$bookCoverUrl}" class="listResultImage img-thumbnail {$coverStyle}" alt="{translate text='Cover Image' inAttribute=true isPublicFacing=true}">
						</a>
					{/if}
				</div>
			{/if}

			<div class="{if empty($showCovers)}col-xs-12{else}col-xs-9 col-sm-9 col-md-9 col-lg-10{/if}">{* May turn out to be more than one situation to consider here *}
				{* Title Row *}

				<div class="row">
					<div class="col-xs-12">
						<span class="result-index">{$resultIndex})</span>&nbsp;
						<a href="{if $bypassEventPage}{$recordDriver->getExternalUrl()}{else}{$eventUrl}{/if}" class="result-title notranslate" onclick="AspenDiscovery.Events.trackUsage('{$id}')">
							{if !$title|removeTrailingPunctuation} {translate text='Title not available' isPublicFacing=true}{else}{$title|removeTrailingPunctuation|highlight|truncate:180:"..."}{/if}
						</a>
						{if isset($summScore)}
							&nbsp;(<a href="#" onclick="return AspenDiscovery.showElementInPopup('Score Explanation', '#scoreExplanationValue{$id|escape}');">{$summScore}</a>)
						{/if}
						{if isset($summExplain)}
							<div class="hidden" id="scoreExplanationValue{$id|escape}">{$summExplain}</div>
						{/if}
					</div>
				</div>

				<div class="row">
					<div class="result-label col-tn-2">{translate text="Date" isPublicFacing=true} </div>
					<div class="result-value col-tn-6 notranslate">
						{if $allDayEvent}
							{$start_date|date_format:"%a %b %e, %Y"}{translate text=" - All Day Event" isPublicFacing=true}
						{elseif $multiDayEvent}
							{$start_date|date_format:"%a %b %e, %Y %l:%M%p"} to {$end_date|date_format:"%a %b %e, %Y %l:%M%p"}
						{else}
							{$start_date|date_format:"%a %b %e, %Y from %l:%M%p"} to {$end_date|date_format:"%l:%M%p"}
						{/if}
						{if !empty($isCancelled)}
							&nbsp;<span class="label label-danger">{translate text="Cancelled" isPublicFacing=true}</span>
						{/if}
					</div>
				</div>
				<div class="row">
					<div class="result-label col-tn-2">{translate text="Location" isPublicFacing=true} </div>
					<div class="result-value col-tn-6 notranslate">
						{$recordDriver->getBranch()}
					</div>
					{if !empty($recordDriver->getRoom())}
						<br />
						<div class="result-label col-tn-2">{translate text="Sublocation" isPublicFacing=true} </div>
						<div class="result-value col-tn-6 notranslate">
							{$recordDriver->getRoom()}
						</div>
					{/if}
					{if $private}
						<div class="result-value col-tn-8">
							<span class="label label-default">{translate text="Private" isPublicFacing=true}</span>
						</div>
					{/if}
					{* Register Button *}
					<div class="result-value col-tn-4">
						{if $recordDriver->inEvents()}
							{if $recordDriver->isRegistrationRequired()}
								<div class="btn-toolbar">
									<div class="btn-group btn-group-vertical btn-block">
										{if $recordDriver->isRegisteredForEvent()}
											<a href="{$recordDriver->getExternalUrl()}" class="btn btn-sm btn-action btn-wrap" target="_blank" style="width:100%" aria-label="{translate text="You Are Registered" isPublicFacing=true inAttribute=true} ({translate text='opens in new window' isPublicFacing=true inAttribute=true})"><i class="fas fa-external-link-alt" role="presentation"></i> {translate text="You Are Registered" isPublicFacing=true}</a>
										{else}
											{if !empty($recordDriver->getRegistrationModalBody())}
												<a class="btn btn-sm btn-action btn-register btn-wrap" onclick="return AspenDiscovery.Account.regInfoModal(this, 'Events', '{$recordDriver->getUniqueID()|escape}', 'assabet', '{$recordDriver->getExternalUrl()}');" style="width:100%">{translate text="Registration Information" isPublicFacing=true}
												</a>
											{else}
												<a href="{$recordDriver->getExternalUrl()}" class="btn btn-sm btn-action btn-register btn-wrap" target="_blank" style="width:100%"aria-label="{translate text="Registration Information" isPublicFacing=true inAttribute=true} ({translate text='opens in new window' isPublicFacing=true inAttribute=true})"><i class="fas fa-external-link-alt" role="presentation"></i> {translate text="Registration Information" isPublicFacing=true}</a>
											{/if}
										{/if}
										<a href="/MyAccount/MyEvents?page=1&eventsFilter=upcoming" class="btn btn-sm btn-action btn-wrap" style="width:100%">{translate text="Go To Your Events" isPublicFacing=true}</a>
									</div>
								</div>
								<br>
							{else}
								<div class="btn-toolbar">
									<a href="/MyAccount/MyEvents?page=1&eventsFilter=upcoming" class="btn btn-sm btn-action btn-wrap" style="width:100%">{translate text="In Your Events" isPublicFacing=true}</a>
								</div>
								<br>
							{/if}
						{else}
							{if $recordDriver->isRegistrationRequired()}
								<div class="btn-toolbar">
									<div class="btn-group btn-group-vertical btn-block">
										{if !empty($recordDriver->getRegistrationModalBody())}
											<a class="btn btn-sm btn-action btn-register btn-wrap" onclick="return AspenDiscovery.Account.regInfoModal(this, 'Events', '{$recordDriver->getUniqueID()|escape}', 'assabet', '{$recordDriver->getExternalUrl()}');">{translate text="Registration Information" isPublicFacing=true}
											</a>
										{else}
											<a href="{$recordDriver->getExternalUrl()}" class="btn btn-sm btn-action btn-register btn-wrap" target="_blank" style="width:100%"aria-label="{translate text="Registration Information" isPublicFacing=true inAttribute=true} ({translate text='opens in new window' isPublicFacing=true inAttribute=true})"><i class="fas fa-external-link-alt" role="presentation"></i> {translate text="Registration Information" isPublicFacing=true}</a>
										{/if}
										{if empty($offline) || $enableEContentWhileOffline}
											<a onclick="return AspenDiscovery.Account.saveEvent(this, 'Events', '{$recordDriver->getUniqueID()|escape}', 'aspenEvents');" class="btn btn-sm btn-action btn-wrap addToYourEventsBtn" style="width:100%">{translate text="Add to Your Events" isPublicFacing=true}</a>
										{/if}
									</div>
								</div>
								<br>
							{elseif empty($offline) || $enableEContentWhileOffline}
								<div class="btn-toolbar">
									<a onclick="return AspenDiscovery.Account.saveEvent(this, 'Events', '{$recordDriver->getUniqueID()|escape}', 'assabet');" class="btn btn-sm btn-action btn-wrap addToYourEventsBtn" style="width:100%">{translate text="Add to Your Events" isPublicFacing=true}</a>
								</div>
								<br>
							{/if}
						{/if}
					</div>
				</div>

				{* Description Section *}
				{if !empty($description)}
					<div class="row visible-xs">
						<div class="result-label col-tn-3 col-xs-3">{translate text="Description" isPublicFacing=true}</div>
						<div class="result-value col-tn-9 col-xs-9"><a id="descriptionLink{$resultIndex|escape}" href="#" onclick="$('#descriptionValue{$resultIndex|escape},#descriptionLink{$resultIndex|escape}').toggleClass('hidden-xs');return false;">{translate text="Click to view" isPublicFacing=true}</a></div>
					</div>

					<div class="row">
						{* Hide in mobile view *}
						<div class="result-value hidden-xs col-sm-12" id="descriptionValue{$resultIndex|escape}">
							{$description|highlight|truncate_html:450:"..."}
						</div>
					</div>
				{/if}

				<div class="row">
					<div class="col-xs-12">
						{include file='Events/result-tools-horizontal.tpl' recordUrl=$eventUrl showMoreInfo=true}
					</div>
				</div>
			</div>
		</div>
	</div>
{/strip}
