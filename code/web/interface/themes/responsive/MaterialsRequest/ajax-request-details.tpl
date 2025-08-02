{strip}
{if !empty($error)}
	<div class="alert alert-danger">{$error}</div>
{else}
	<div>
		{foreach from=$requestFormFields key=category item=formFields}
			<fieldset>
				<legend>{translate text=$category isPublicFacing=true isAdminFacing=true}</legend>
				{foreach from=$formFields item=formField}
					{if $formField->fieldType == 'format'}
						{assign var="materialRequestTableColumnName" value=$formField->fieldType}
						<div class="request_detail_field row">
							<label class="request_detail_field_label col-sm-3">{translate text=$formField->fieldLabel isPublicFacing=true isAdminFacing=true} </label>
							<div class="request_detail_field_value col-sm-9">
								{translate text=$materialsRequest->getDisplayFormat() isPublicFacing=true isAdminFacing=true}
							</div>
						</div>
						{if !empty($materialsRequest->specialFields)}
							{foreach from=$materialsRequest->specialFields item="specialField"}
								{if $specialField == 'Abridged/Unabridged'}
									{if $materialsRequest->abridged != 2}
										<div class="request_detail_field row">
											<label class="request_detail_field_label col-sm-3">{translate text="Abridged" isPublicFacing=true isAdminFacing=true} </label>
											<div class=" request_detail_field_value col-sm-9">
												{if $materialsRequest->abridged == 1}{translate text="Abridged Version" isPublicFacing=true isAdminFacing=true}{elseif $materialsRequest->abridged == 0}{translate text="Unabridged Version" isPublicFacing=true isAdminFacing=true}{/if}
											</div>
										</div>
									{/if}
								{elseif $specialField == 'Article Field'}
									<div class=" request_detail_field row">
										<label class="request_detail_field_label col-sm-3">{translate text="Magazine/Journal Title" isPublicFacing=true isAdminFacing=true} </label>
										<div class=" request_detail_field_value col-sm-9">{$materialsRequest->magazineTitle}</div>
									</div>
									<div class=" request_detail_field row">
										<label class="request_detail_field_label col-sm-3">{translate text="Date"  isPublicFacing=true isAdminFacing=true}</label>
										<div class=" request_detail_field_value col-sm-9">{$materialsRequest->magazineDate}</div>
									</div>
									<div class=" request_detail_field row">
										<label class="request_detail_field_label col-sm-3">{translate text="Volume" isPublicFacing=true isAdminFacing=true}</label>
										<div class=" request_detail_field_value col-sm-9">{$materialsRequest->magazineVolume}</div>
									</div>
									<div class=" request_detail_field row">
										<label class="request_detail_field_label col-sm-3">{translate text="Number" isPublicFacing=true isAdminFacing=true}</label>
										<div class=" request_detail_field_value col-sm-9">{$materialsRequest->magazineNumber}</div>
									</div>
									<div class=" request_detail_field row">
										<label class="request_detail_field_label col-sm-3">{translate text="Page Numbers" isPublicFacing=true isAdminFacing=true}</label>
										<div class=" request_detail_field_value col-sm-9">{$materialsRequest->magazinePageNumbers}</div>
									</div>

									{* ebook and eaudio use the same database table column subformat *}
								{elseif $specialField == 'Eaudio format'}
									<div class=" request_detail_field row">
										<label class="request_detail_field_label col-sm-3">{translate text="E-audio format" isPublicFacing=true isAdminFacing=true}</label>
										<div class=" request_detail_field_value col-sm-9">{translate text=$materialsRequest->subFormat isPublicFacing=true isAdminFacing=true}</div>
									</div>
								{elseif $specialField == 'Ebook format'}
									<div class="request_detail_field row">
										<label class="request_detail_field_label col-sm-3">{translate text="E-book format" isPublicFacing=true isAdminFacing=true}</label>
										<div class=" request_detail_field_value col-sm-9">{translate text=$materialsRequest->subFormat isPublicFacing=true isAdminFacing=true}</div>
									</div>
								{elseif $specialField == 'Season'}
									<div class="request_detail_field row">
										<label class="request_detail_field_label col-sm-3">{translate text="Season" isPublicFacing=true isAdminFacing=true}</label>
										<div class="request_detail_field_value col-sm-9">
											{$materialsRequest->season}
										</div>
									</div>
								{/if}

							{/foreach}
						{/if}

					{elseif $formField->fieldType == 'author'}
						<div class="request_detail_field row">
							<label class="request_detail_field_label col-sm-3">{translate text=$materialsRequest->authorLabel isPublicFacing=true isAdminFacing=true} </label>
							<div class="request_detail_field_value col-sm-9">{$materialsRequest->author}</div>
						</div>

					{elseif
						$formField->fieldType == 'id' ||
						$formField->fieldType == 'comments' ||
						$formField->fieldType == 'about' ||
						$formField->fieldType == 'title' ||
						$formField->fieldType == 'ageLevel' ||
						$formField->fieldType == 'isbn'||
						$formField->fieldType == 'oclcNumber' ||
						$formField->fieldType == 'publisher' ||
						$formField->fieldType == 'publicationYear' ||
						$formField->fieldType == 'articleInfo' ||
						$formField->fieldType == 'phone' ||
						$formField->fieldType == 'email' ||
						$formField->fieldType == 'magazineTitle' ||
						$formField->fieldType == 'magazineDate' ||
						$formField->fieldType == 'magazineVolume' ||
						$formField->fieldType == 'magazineNumber' ||
						$formField->fieldType == 'magazinePageNumbers' ||
						$formField->fieldType == 'upc' ||
						$formField->fieldType == 'issn' ||
						$formField->fieldType == 'bookmobileStop' ||
						$formField->fieldType == 'season'}
						{assign var="materialRequestTableColumnName" value=$formField->fieldType}
						<div class="request_detail_field row">
							<label class="request_detail_field_label col-sm-3">{translate text=$formField->fieldLabel isPublicFacing=true isAdminFacing=true} </label>
							<div class="request_detail_field_value col-sm-9">
								{$materialsRequest->$materialRequestTableColumnName}
							</div>
						</div>

					{elseif $formField->fieldType == 'bookType'}
						{assign var="materialRequestTableColumnName" value=$formField->fieldType}
						{*{assign var="fieldValue" value=$materialsRequest->$materialRequestTableColumnName}*}
						<div class="request_detail_field row">
							<label class="request_detail_field_label col-sm-3">{translate text=$formField->fieldLabel isPublicFacing=true isAdminFacing=true} </label>
							<div class="request_detail_field_value col-sm-9">
								{translate text=$materialsRequest->$materialRequestTableColumnName|capitalize isPublicFacing=true isAdminFacing=true}
							</div>
						</div>

					{elseif $formField->fieldType == 'status'}
						<div class="request_detail_field row">
							<label class="request_detail_field_label col-sm-3">{translate text=$formField->fieldLabel isPublicFacing=true isAdminFacing=true} </label>
							<div class=" request_detail_field_value col-sm-9">
								{translate text=$materialsRequest->statusLabel isPublicFacing=true isAdminFacing=true}
							</div>
						</div>

					{elseif $formField->fieldType == 'dateCreated'|| $formField->fieldType == 'dateUpdated'}
						{* Date Fields *}
						{assign var="materialRequestTableColumnName" value=$formField->fieldType}
						<div class="request_detail_field row">
							<label class="request_detail_field_label col-sm-3">{translate text=$formField->fieldLabel isPublicFacing=true isAdminFacing=true} </label>
							<div class="request_detail_field_value col-sm-9">
								{$materialsRequest->$materialRequestTableColumnName|date_format}
							</div>
						</div>

					{elseif $formField->fieldType == 'emailSent' || $formField->fieldType == 'holdsCreated'}
						{* Yes / No Fields *}
						{assign var="materialRequestTableColumnName" value=$formField->fieldType}
						<div class="request_detail_field row">
							<label class="request_detail_field_label col-sm-3">{translate text=$formField->fieldLabel isPublicFacing=true isAdminFacing=true} </label>
							<div class="request_detail_field_value col-sm-9">
								{if $materialsRequest->$materialRequestTableColumnName == 1}{translate text="Yes" isPublicFacing=true isAdminFacing=true}
								{elseif $materialsRequest->$materialRequestTableColumnName == 0}{translate text="No" isPublicFacing=true isAdminFacing=true}
								{/if}
							</div>
						</div>

					{* USER INFORMATION FIELDS  *}

					{elseif $formField->fieldType == 'createdBy'}
						{if !empty($showUserInformation)}
							<div class="request_detail_field row">
								<label class="request_detail_field_label col-sm-3">{translate text=$formField->fieldLabel isPublicFacing=true isAdminFacing=true} </label>
								<div class="request_detail_field_value col-sm-9">
									{$requestUser->firstname|escape} {$requestUser->lastname|escape}
								</div>
							</div>
						{/if}

					{elseif $formField->fieldType == 'phone' || $formField->fieldType == 'email'}
						{if !empty($showUserInformation)}
							{assign var="materialRequestTableColumnName" value=$formField->fieldType}
							<div class="request_detail_field row">
								<label class="request_detail_field_label col-sm-3">{translate text=$formField->fieldLabel isPublicFacing=true isAdminFacing=true} </label>
								<div class="request_detail_field_value col-sm-9">
									{$materialsRequest->$materialRequestTableColumnName}
								</div>
							</div>
						{/if}

					{elseif $formField->fieldType == 'illItem' || $formField->fieldType == 'placeHoldWhenAvailable'}
						{* Yes / No  User Information Fields *}
						{if !empty($showUserInformation)}
							{assign var="materialRequestTableColumnName" value=$formField->fieldType}
							<div class="request_detail_field row">
								<label class="request_detail_field_label col-sm-3">{translate text=$formField->fieldLabel isPublicFacing=true isAdminFacing=true} </label>
								<div class="request_detail_field_value col-sm-9">
									{if $materialsRequest->$materialRequestTableColumnName == 1}{translate text="Yes" isPublicFacing=true isAdminFacing=true}
									{elseif $materialsRequest->$materialRequestTableColumnName == 0}{translate text="No" isPublicFacing=true isAdminFacing=true}
									{/if}
								</div>
							</div>
						{/if}

					{elseif $formField->fieldType == 'holdPickupLocation'}
						{if !empty($showUserInformation)}
							<div class="request_detail_field row">
								<label class="request_detail_field_label col-sm-3">{translate text=$formField->fieldLabel isPublicFacing=true isAdminFacing=true} </label>
								<div class=" request_detail_field_value col-sm-9">
									{$materialsRequest->location|escape}
									{*{if $materialsRequest->bookmobileStop}{$materialsRequest->bookmobileStop}{/if}*}
								</div>
							</div>
						{/if}
					{elseif $formField->fieldType == 'libraryCardNumber'}
						{if !empty($showUserInformation)}
							{if !empty($barCodeColumn)}
								<div class="row form-group">
									<label class="control-label col-sm-3">{translate text=$formField->fieldLabel isPublicFacing=true isAdminFacing=true} </label>
									<div class="request_detail_field_value col-sm-9">
										{$requestUser->$barCodeColumn}
									</div>
								</div>
							{/if}
						{/if}
					{elseif $formField->fieldType == 'source'}
						<div class="row form-group">
							<label class="control-label col-sm-3">{translate text=$formField->fieldLabel isPublicFacing=true isAdminFacing=true} </label>
							<div class="request_detail_field_value col-sm-9">
								{if $materialsRequest->source == 1}{translate text="Materials Request" isAdminFacing=true}{else}{translate text="Local ILL" isAdminFacing=true}{/if}
							</div>
						</div>
					{/if}
				{/foreach}
			</fieldset>
		{/foreach}
	</div>
{/if}
{/strip}
