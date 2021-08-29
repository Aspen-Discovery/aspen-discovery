{strip}
	{if !empty($error)}
	<div class="alert alert-danger">{$error}</div>
{else}
	<div>
		{foreach from=$requestFormFields key=category item=formFields}
			<fieldset>
				<legend>{$category|translate}</legend>
				{foreach from=$formFields item=formField}
					{if $formField->fieldType == 'format'}
						{assign var="materialRequestTableColumnName" value=$formField->fieldType}
						<div class="request_detail_field row">
							<label class="request_detail_field_label col-sm-3">{$formField->fieldLabel|translate} </label>
							<div class="request_detail_field_value col-sm-9">
								{if $materialsRequest->formatLabel}
									{$materialsRequest->formatLabel|translate}
								{else}{* Fallback if no label is found*}
									{$materialsRequest->format|translate}
								{/if}
							</div>
						</div>
						{if !empty($materialsRequest->specialFields)}
							{foreach from=$materialsRequest->specialFields item="specialField"}

								{if $specialField == 'Abridged/Unabridged'}
									{if $materialsRequest->abridged != 2}
										<div class="request_detail_field row">
											<label class="request_detail_field_label col-sm-3">{translate text="Abridged"} </label>
											<div class=" request_detail_field_value col-sm-9">
												{if $materialsRequest->abridged == 1}{translate text="Abridged Version"}{elseif $materialsRequest->abridged == 0}{translate text="Unabridged Version"}{/if}
											</div>
										</div>
									{/if}
								{elseif $specialField == 'Article Field'}
									<div class=" request_detail_field row">
										<label class="request_detail_field_label col-sm-3">{translate text="Magazine/Journal Title"} </label>
										<div class=" request_detail_field_value col-sm-9">{$materialsRequest->magazineTitle}</div>
									</div>
									<div class=" request_detail_field row">
										<label class="request_detail_field_label col-sm-3">{translate text="Date" isPublicFacing=true}</label>
										<div class=" request_detail_field_value col-sm-9">{$materialsRequest->magazineDate}</div>
									</div>
									<div class=" request_detail_field row">
										<label class="request_detail_field_label col-sm-3">{translate text="Volume"}</label>
										<div class=" request_detail_field_value col-sm-9">{$materialsRequest->magazineVolume}</div>
									</div>
									<div class=" request_detail_field row">
										<label class="request_detail_field_label col-sm-3">{translate text="Number"}</label>
										<div class=" request_detail_field_value col-sm-9">{$materialsRequest->magazineNumber}</div>
									</div>
									<div class=" request_detail_field row">
										<label class="request_detail_field_label col-sm-3">{translate text="Page Numbers"}</label>
										<div class=" request_detail_field_value col-sm-9">{$materialsRequest->magazinePageNumbers}</div>
									</div>

									{* ebook and eaudio use the same database table column subformat *}
								{elseif $specialField == 'Eaudio format'}
									<div class=" request_detail_field row">
										<label class="request_detail_field_label col-sm-3">{translate text="E-audio format"}</label>
										<div class=" request_detail_field_value col-sm-9">{$materialsRequest->subFormat|translate}</div>
									</div>
								{elseif $specialField == 'Ebook format'}
									<div class="request_detail_field row">
										<label class="request_detail_field_label col-sm-3">{translate text="E-book format"}</label>
										<div class=" request_detail_field_value col-sm-9">{$materialsRequest->subFormat|translate}</div>
									</div>
								{elseif $specialField == 'Season'}
									<div class="request_detail_field row">
										<label class="request_detail_field_label col-sm-3">{translate text="Season"}</label>
										<div class="request_detail_field_value col-sm-9">
											{$materialsRequest->season}
										</div>
									</div>
								{/if}

							{/foreach}
						{/if}

						{elseif $formField->fieldType == 'author'}
						<div class="request_detail_field row">
							<label class="request_detail_field_label col-sm-3">{$materialsRequest->authorLabel|translate} </label>
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
							<label class="request_detail_field_label col-sm-3">{$formField->fieldLabel|translate} </label>
							<div class="request_detail_field_value col-sm-9">
								{$materialsRequest->$materialRequestTableColumnName}
							</div>
						</div>

					{elseif $formField->fieldType == 'bookType'}
						{assign var="materialRequestTableColumnName" value=$formField->fieldType}
						{*{assign var="fieldValue" value=$materialsRequest->$materialRequestTableColumnName}*}
						<div class="request_detail_field row">
							<label class="request_detail_field_label col-sm-3">{$formField->fieldLabel|translate} </label>
							<div class="request_detail_field_value col-sm-9">
								{$materialsRequest->$materialRequestTableColumnName|translate|capitalize}
							</div>
						</div>

						{elseif $formField->fieldType == 'status'}
						<div class="request_detail_field row">
							<label class="request_detail_field_label col-sm-3">{$formField->fieldLabel|translate} </label>
							<div class=" request_detail_field_value col-sm-9">
								{$materialsRequest->statusLabel|translate}
							</div>
						</div>

						{elseif
						$formField->fieldType == 'dateCreated'||
						$formField->fieldType == 'dateUpdated'}
						{* Date Fields *}
						{assign var="materialRequestTableColumnName" value=$formField->fieldType}
						<div class="request_detail_field row">
							<label class="request_detail_field_label col-sm-3">{$formField->fieldLabel|translate} </label>
							<div class="request_detail_field_value col-sm-9">
								{$materialsRequest->$materialRequestTableColumnName|date_format}
							</div>
						</div>

						{elseif $formField->fieldType == 'emailSent' ||
						$formField->fieldType == 'holdsCreated'}
						{* Yes / No Fields *}
						{assign var="materialRequestTableColumnName" value=$formField->fieldType}
						<div class="request_detail_field row">
							<label class="request_detail_field_label col-sm-3">{$formField->fieldLabel|translate} </label>
							<div class="request_detail_field_value col-sm-9">
								{if $materialsRequest->$materialRequestTableColumnName == 1}{translate text="Yes"}
								{elseif $materialsRequest->$materialRequestTableColumnName == 0}{translate text="No"}
								{/if}
							</div>
						</div>

						{* USER INFORMATION FIELDS  *}

						{elseif $formField->fieldType == 'createdBy'}
						{if $showUserInformation}
							<div class="request_detail_field row">
								<label class="request_detail_field_label col-sm-3">{$formField->fieldLabel|translate} </label>
								<div class="request_detail_field_value col-sm-9">
									{$requestUser->firstname} {$requestUser->lastname}
								</div>
							</div>
						{/if}

						{elseif
						$formField->fieldType == 'phone' ||
						$formField->fieldType == 'email'}
							{if $showUserInformation}
								{assign var="materialRequestTableColumnName" value=$formField->fieldType}
								<div class="request_detail_field row">
									<label class="request_detail_field_label col-sm-3">{$formField->fieldLabel|translate} </label>
									<div class="request_detail_field_value col-sm-9">
										{$materialsRequest->$materialRequestTableColumnName}
									</div>
								</div>
							{/if}

						{elseif
						$formField->fieldType == 'illItem' ||
						$formField->fieldType == 'placeHoldWhenAvailable'}
						{* Yes / No  User Information Fields *}
							{if $showUserInformation}
								{assign var="materialRequestTableColumnName" value=$formField->fieldType}
								<div class="request_detail_field row">
									<label class="request_detail_field_label col-sm-3">{$formField->fieldLabel|translate} </label>
									<div class="request_detail_field_value col-sm-9">
										{if $materialsRequest->$materialRequestTableColumnName == 1}{translate text="Yes"}
										{elseif $materialsRequest->$materialRequestTableColumnName == 0}{translate text="No"}
										{/if}
									</div>
								</div>
							{/if}

						{elseif $formField->fieldType == 'holdPickupLocation'}
							{if $showUserInformation}
								<div class="request_detail_field row">
									<label class="request_detail_field_label col-sm-3">{$formField->fieldLabel|translate} </label>
									<div class=" request_detail_field_value col-sm-9">
										{$materialsRequest->location}
										{*{if $materialsRequest->bookmobileStop}{$materialsRequest->bookmobileStop}{/if}*}
									</div>
								</div>
							{/if}
							{elseif $formField->fieldType == 'libraryCardNumber'}
							{if $showUserInformation}
								{if $barCodeColumn}
									<div class="row form-group">
										<label class="control-label col-sm-3">{$formField->fieldLabel|translate} </label>
										<div class="request_detail_field_value col-sm-9">
											{$requestUser->$barCodeColumn}
										</div>
									</div>
								{/if}
							{/if}
						{/if}
{*
					</div>
*}
				{/foreach}
			</fieldset>
		{/foreach}




<!--

		<fieldset>
			<legend>Basic Information</legend>
			{*<div class="request_detail_field row">*}
				{*<label class="request_detail_field_label col-sm-3">Format: </label>*}
				{*<div class=" request_detail_field_value col-sm-9">{$materialsRequest->format}</div>*}
			{*</div>*}
			{*<div class=" request_detail_field row">*}
				{*<label class="request_detail_field_label col-sm-3">Title: </label>*}
				{*<div class=" request_detail_field_value col-sm-9">{$materialsRequest->title}</div>*}
			{*</div>*}



		</fieldset>

		<fieldset>
			<legend>Supplemental Details</legend>
			{if $materialsRequest->bookType}
				<div class=" request_detail_field row">
					<label class="request_detail_field_label col-sm-3">Type: </label>
					<div class="request_detail_field_value col-sm-9">{$materialsRequest->bookType|translate|ucfirst}</div>
				</div>
			{/if}
		</fieldset>

	-->
{/if}
{/strip}