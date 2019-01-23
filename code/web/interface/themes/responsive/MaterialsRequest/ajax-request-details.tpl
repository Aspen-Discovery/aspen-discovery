{if $error}
	<div class="alert alert-danger">{$error}</div>
{else}
	<div>
		{foreach from=$requestFormFields key=category item=formFields}
			<fieldset>
				<legend>{$category}</legend>
				{foreach from=$formFields item=formField}
					{*{if $formField->fieldType == 'text' ||*}
					{*$formField->fieldType == 'textarea'}*}
					{*<div class="request_detail_field row">*}
						{*<label class="request_detail_field_label col-sm-3">{$formField->fieldLabel}: </label>*}
						{*<div class="request_detail_field_value col-sm-9">*}
							{*{if $formField->id && array_key_exists($formField->id, $additionalRequestData)}*}
								{*{assign var='formfieldId' value=$formField->id}*}
								{*{$additionalRequestData.$formfieldId}*}
							{*{/if}*}
						{*</div>*}
					{*</div>*}

					{*{elseif $formField->fieldType == 'yes/no'}*}

					{* Yes / No Fields *}
					{*<div class="request_detail_field row">*}
						{*<label class="request_detail_field_label col-sm-3">{$formField->fieldLabel}: </label>*}
						{*<div class="request_detail_field_value col-sm-9">*}
							{*{if $formField->id && array_key_exists($formField->id, $additionalRequestData)}*}
								{*{assign var='formfieldId' value=$formField->id}*}
								{*{if $additionalRequestData.$formfieldId == 1}Yes*}
								{*{elseif $additionalRequestData.$formfieldId == 1}No*}
								{*{/if}*}
							{*{else}*}
								{*No*}
							{*{/if}*}
						{*</div>*}
					{*</div>*}


					{if $formField->fieldType == 'format'}
						{assign var="materialRequestTableColumnName" value=$formField->fieldType}
						<div class="request_detail_field row">
							<label class="request_detail_field_label col-sm-3">{$formField->fieldLabel}: </label>
							<div class="request_detail_field_value col-sm-9">
								{if $materialsRequest->formatLabel}
									{$materialsRequest->formatLabel}
								{else}{* Fallback if no label is found*}
									{$materialsRequest->format}
								{/if}
							</div>
						</div>
						{if !empty($materialsRequest->specialFields)}
							{foreach from=$materialsRequest->specialFields item="specialField"}

								{if $specialField == 'Abridged/Unabridged'}
									{if $materialsRequest->abridged != 2}
										<div class="request_detail_field row">
											<label class="request_detail_field_label col-sm-3">Abridged: </label>
											<div class=" request_detail_field_value col-sm-9">
												{if $materialsRequest->abridged == 1}Abridged Version{elseif $materialsRequest->abridged == 0}Unabridged Version{/if}
											</div>
										</div>
									{/if}
								{elseif $specialField == 'Article Field'}
									<div class=" request_detail_field row">
										<label class="request_detail_field_label col-sm-3">Magazine/Journal Title: </label>
										<div class=" request_detail_field_value col-sm-9">{$materialsRequest->magazineTitle}</div>
									</div>
									<div class=" request_detail_field row">
										<label class="request_detail_field_label col-sm-3">Date: </label>
										<div class=" request_detail_field_value col-sm-9">{$materialsRequest->magazineDate}</div>
									</div>
									<div class=" request_detail_field row">
										<label class="request_detail_field_label col-sm-3">Volume: </label>
										<div class=" request_detail_field_value col-sm-9">{$materialsRequest->magazineVolume}</div>
									</div>
									<div class=" request_detail_field row">
										<label class="request_detail_field_label col-sm-3">Number: </label>
										<div class=" request_detail_field_value col-sm-9">{$materialsRequest->magazineNumber}</div>
									</div>
									<div class=" request_detail_field row">
										<label class="request_detail_field_label col-sm-3">Page Numbers: </label>
										<div class=" request_detail_field_value col-sm-9">{$materialsRequest->magazinePageNumbers}</div>
									</div>

									{* ebook and eaudio use the same database table column subformat *}
								{elseif $specialField == 'Eaudio format'}
									<div class=" request_detail_field row">
										<label class="request_detail_field_label col-sm-3">E-audio format: </label>
										<div class=" request_detail_field_value col-sm-9">{$materialsRequest->subFormat|translate}</div>
									</div>
								{elseif $specialField == 'Ebook format'}
									<div class="request_detail_field row">
										<label class="request_detail_field_label col-sm-3">E-book format: </label>
										<div class=" request_detail_field_value col-sm-9">{$materialsRequest->subFormat|translate}</div>
									</div>
								{elseif $specialField == 'Season'}
									<div class="request_detail_field row">
										<label class="request_detail_field_label col-sm-3">Season: </label>
										<div class="request_detail_field_value col-sm-9">
											{$materialsRequest->season}
										</div>
									</div>
								{/if}

							{/foreach}
						{/if}

						{elseif $formField->fieldType == 'author'}
						<div class="request_detail_field row">
							<label class="request_detail_field_label col-sm-3">{$materialsRequest->authorLabel}: </label>
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
							<label class="request_detail_field_label col-sm-3">{$formField->fieldLabel}: </label>
							<div class="request_detail_field_value col-sm-9">
								{$materialsRequest->$materialRequestTableColumnName}
							</div>
						</div>

					{elseif $formField->fieldType == 'bookType'}
						{assign var="materialRequestTableColumnName" value=$formField->fieldType}
						{*{assign var="fieldValue" value=$materialsRequest->$materialRequestTableColumnName}*}
						<div class="request_detail_field row">
							<label class="request_detail_field_label col-sm-3">{$formField->fieldLabel}: </label>
							<div class="request_detail_field_value col-sm-9">
								{$materialsRequest->$materialRequestTableColumnName|translate|capitalize}
							</div>
						</div>

						{elseif $formField->fieldType == 'status'}
						<div class="request_detail_field row">
							<label class="request_detail_field_label col-sm-3">{$formField->fieldLabel}: </label>
							<div class=" request_detail_field_value col-sm-9">
								{$materialsRequest->statusLabel}
							</div>
						</div>

						{elseif
						$formField->fieldType == 'dateCreated'||
						$formField->fieldType == 'dateUpdated'}
						{* Date Fields *}
						{assign var="materialRequestTableColumnName" value=$formField->fieldType}
						<div class="request_detail_field row">
							<label class="request_detail_field_label col-sm-3">{$formField->fieldLabel}: </label>
							<div class="request_detail_field_value col-sm-9">
								{$materialsRequest->$materialRequestTableColumnName|date_format}
							</div>
						</div>

						{elseif $formField->fieldType == 'emailSent' ||
						$formField->fieldType == 'holdsCreated'}
						{* Yes / No Fields *}
						{assign var="materialRequestTableColumnName" value=$formField->fieldType}
						<div class="request_detail_field row">
							<label class="request_detail_field_label col-sm-3">{$formField->fieldLabel}: </label>
							<div class="request_detail_field_value col-sm-9">
								{if $materialsRequest->$materialRequestTableColumnName == 1}Yes
								{elseif $materialsRequest->$materialRequestTableColumnName == 0}No
								{/if}
							</div>
						</div>

						{* USER INFORMATION FIELDS  *}

						{elseif $formField->fieldType == 'createdBy'}
						{if $showUserInformation}
							<div class="request_detail_field row">
								<label class="request_detail_field_label col-sm-3">{$formField->fieldLabel}: </label>
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
									<label class="request_detail_field_label col-sm-3">{$formField->fieldLabel}: </label>
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
									<label class="request_detail_field_label col-sm-3">{$formField->fieldLabel}: </label>
									<div class="request_detail_field_value col-sm-9">
										{if $materialsRequest->$materialRequestTableColumnName == 1}Yes
										{elseif $materialsRequest->$materialRequestTableColumnName == 0}No
										{/if}
									</div>
								</div>
							{/if}

						{elseif $formField->fieldType == 'holdPickupLocation'}
							{if $showUserInformation}
								<div class="request_detail_field row">
									<label class="request_detail_field_label col-sm-3">{$formField->fieldLabel}: </label>
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
										<label class="control-label col-sm-3">{$formField->fieldLabel}: </label>
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
