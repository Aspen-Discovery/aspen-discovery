{strip}

<div class="materialsRequestLoggedInFields" {if !$loggedIn}style="display:none"{/if}>
{foreach from=$requestFormFields key=category item=formFields}
	<fieldset>
		<legend>{$category}</legend>
		{foreach from=$formFields item=formField}

			{if $formField->fieldType == 'format'}
				{assign var="materialRequestTableColumnName" value=$formField->fieldType}
				<div class="row form-group">
					<label class="control-label col-sm-3" for="format">{$formField->fieldLabel}: <span class="requiredIndicator">*</span></label>
					<div class="request_detail_field_value col-sm-9">

						<select name="format" class="required form-control" id="format" onchange="VuFind.MaterialsRequest.setFieldVisibility();">
							{* For New Requests, set the first format as the one selected by default *}
							{foreach from=$availableFormats item=label key=formatKey}
								<option value="{$formatKey}"{if $materialsRequest->format==$formatKey} selected='selected'{/if}>{$label}</option>
							{/foreach}
						</select>

					</div>
				</div>

				{* Special Fields *}

				{* Article Fields *}
				<div class="form-group specialFormatField articleField">
					<label for="magazineTitle" class="col-sm-3 control-label">Magazine/Journal Title: <span class="requiredIndicator">*</span> </label>
					<div class="col-sm-9">
						<input name="magazineTitle" id="magazineTitle" size="90" maxlength="255" class="required form-control" value="{$materialsRequest->magazineTitle}">
					</div>
				</div>
				<div class="form-group specialFormatField articleField">
					<label for="magazineDate" class="col-sm-3 control-label">Magazine Date: </label>
					<div class="col-sm-9">
						<input name="magazineDate" id="magazineDate" size="20" maxlength="20" value="{$materialsRequest->magazineDate}" class="form-control">
					</div>
				</div>
				<div class="form-group specialFormatField articleField">
					<label for="magazineVolume" class="col-sm-3 control-label">Magazine Volume: </label>
					<div class="col-sm-9">
						<input name="magazineVolume" id="magazineVolume" size="20" maxlength="20" value="{$materialsRequest->magazineVolume}" class="form-control">
					</div>
				</div>
				<div class="form-group specialFormatField articleField">
					<label for="magazineNumber" class="col-sm-3 control-label">Magazine Number: </label>
					<div class="col-sm-9">
						<input name="magazineNumber" id="magazineNumber" size="20" maxlength="20" value="{$materialsRequest->magazineNumber}" class="form-control">
					</div>
				</div>
				<div class="form-group specialFormatField articleField">
					<label for="magazinePageNumbers" class="col-sm-3 control-label">Magazine Page Numbers: </label>
					<div class="col-sm-9">
						<input name="magazinePageNumbers" id="magazinePageNumbers" size="20" maxlength="20" value="{$materialsRequest->magazinePageNumbers}" class="form-control">
					</div>
				</div>

				{* Season Fields *}
				<div class="form-group seasonField specialFormatField">
					<label for="season" class="col-sm-3 control-label">Season: </label>
					<div class="col-sm-9">
						<input name="season" id="season" size="90" maxlength="80" value="{$materialsRequest->season}" class="form-control">
					</div>
				</div>

				{* Ebook Format Fields *}
				{if $showEbookFormatField}
					<div class="form-group ebookField specialFormatField">
						<label for="ebookFormat" class="col-sm-3 control-label">E-book format: </label>
						<div class="col-sm-9">
							<select name="ebookFormat" id="ebookFormat" class="form-control">
								<option value="epub" {if $materialsRequest->subFormat=='epub'}selected='selected'{/if}>EPUB</option>
								<option value="kindle" {if $materialsRequest->subFormat=='kindle'}selected='selected'{/if}>Kindle</option>
								<option value="pdf" {if $materialsRequest->subFormat=='pdf'}selected='selected'{/if}>PDF</option>
								<option value="other" {if $materialsRequest->subFormat=='other'}selected='selected'{/if}>Other - please specify in comments</option>
							</select>
						</div>
					</div>
				{/if}

				{*E-audio Format Fields *}
		{* Hiding this option.
				{if $showEaudioFormatField}
					<div class="form-group eaudioField specialFormatField">{* eaudioField class used for both special field handling and the older format controlling * }
						<label for="eaudioFormat" class="col-sm-3 control-label">E-audio format: </label>
						<div class="col-sm-9">
							<select name="eaudioFormat" id="eaudioFormat" class="form-control">
								<option value="wma" {if $materialsRequest->subFormat=='wma'}selected='selected'{/if}>WMA</option>
								<option value="mp3" {if $materialsRequest->subFormat=='mp3'}selected='selected'{/if}>MP3</option>
								<option value="other" {if $materialsRequest->subFormat=='other'}selected='selected'{/if}>Other - please specify in comments</option>
							</select>
						</div>
					</div>
				{/if}*}

				{* Abridged Fields *}
				<div class="form-group abridgedField specialFormatField">
					<label class="control-label col-sm-3">Abridged: </label>
					<div class="col-sm-9">
						<label for="unabridged" class="radio-inline"><input type="radio" name="abridged" value="unabridged" id="unabridged" {if $materialsRequest->abridged == 0}checked='checked'{/if}>Unabridged</label>
						<label for="abridged" class="radio-inline"><input type="radio" name="abridged" value="abridged" id="abridged" {if $materialsRequest->abridged == 1}checked='checked'{/if}>Abridged</label>
						<label for="na" class="radio-inline"><input type="radio" name="abridged" value="na" id="na" {if $materialsRequest->abridged == 2}checked='checked'{/if}>Not Applicable</label>
					</div>
				</div>

				{* Book Type Input Fields *}
			{elseif $formField->fieldType == 'bookType'}
				{assign var="materialRequestTableColumnName" value=$formField->fieldType}
					<div class="form-group{* specialFormatField*}">
						<label for="{$materialRequestTableColumnName}" class="control-label col-sm-3">{$formField->fieldLabel}: </label>
						<div class="col-sm-9">
							<select name="{$materialRequestTableColumnName}" id="{$materialRequestTableColumnName}" class="form-control">
								<option value="fiction" {if $materialsRequest->bookType=='fiction'}selected='selected'{/if}>Fiction</option>
								<option value="nonfiction" {if $materialsRequest->bookType=='nonfiction'}selected='selected'{/if}>Non-Fiction</option>
								<option value="graphicNovel" {if $materialsRequest->bookType=='graphicNovel'}selected='selected'{/if}>Graphic Novel</option>
								<option value="unknown" {if (!isset($materialsRequest->bookType) || $materialsRequest->bookType=='unknown')}selected='selected'{/if}>Don't Know</option>
							</select>
						</div>
					</div>


				{if !$new && $useWorldCat}
					<div class="form-group">
						<label for="suggestIdentifiers" class="control-label col-sm-3">WorldCat Look up:</label>
						<div class="col-sm-9">
							<input type="button" id="suggestIdentifiers" value="Find exact match" onclick="return VuFind.MaterialsRequest.getWorldCatIdentifiers();" class="btn btn-primary">
						</div>
					</div>
					<div id="suggestedIdentifiers"></div>
				{/if}

				{* The other Fields to Display (Not special format fields) *}


				{* Readonly Fields *}
			{elseif $formField->fieldType == 'id'}
				{if $isAdminUser && !$new}
					{assign var="hasId" value=1}
					{assign var="materialRequestTableColumnName" value=$formField->fieldType}
					<div class="request_detail_field row">
						<label class="control-label col-sm-3">{$formField->fieldLabel}: </label>
						<div class="request_detail_field_value col-sm-9">
							{$materialsRequest->$materialRequestTableColumnName}
							<input type="hidden" name="{$materialRequestTableColumnName}" id="{$materialRequestTableColumnName}" value="{$materialsRequest->$materialRequestTableColumnName}">
						</div>
					</div>
				{/if}

				{* Author Field *}
			{elseif $formField->fieldType == 'author'}
				<div class="row form-group">
					<label id="authorFieldLabel" class="control-label col-sm-3" for="author">{$materialsRequest->authorLabel}: {*<span class="requiredIndicator">*</span>*}</label>
					<div class="request_detail_field_value col-sm-9">
						<input name="author" id="author" size="90" maxlength="255" class="{*required *}form-control" value="{$materialsRequest->author}">
					</div>
				</div>

				{* Publisher Input Fields *}
			{elseif
			$formField->fieldType == 'publisher' ||
			$formField->fieldType == 'publicationYear'}
				{assign var="materialRequestTableColumnName" value=$formField->fieldType}
				<div class="row form-group">
					<label class="control-label col-sm-3" for="{$materialRequestTableColumnName}">{$formField->fieldLabel}: </label>
					<div class="request_detail_field_value col-sm-9">
						<input name="{$materialRequestTableColumnName}" id="{$materialRequestTableColumnName}"
										size="40" maxlength="255" class="form-control"
										value="{$materialsRequest->$materialRequestTableColumnName}">
					</div>
				</div>


				{* Required Regular Input Field *}
			{elseif
			$formField->fieldType == 'title'}
				{assign var="materialRequestTableColumnName" value=$formField->fieldType}
				<div class="row form-group">
					<label id="titleLabel" for="{$materialRequestTableColumnName}" class="control-label col-sm-3">{$formField->fieldLabel}: <span class='requiredIndicator'>*</span></label>
					<div class="request_detail_field_value col-sm-9">
						<input name="{$materialRequestTableColumnName}" id="{$materialRequestTableColumnName}"
										size="90" maxlength="255"
										class="required form-control"
										value="{$materialsRequest->$materialRequestTableColumnName}">
					</div>
				</div>

				{* Regular Input Field *}
			{elseif
			$formField->fieldType == 'isbn'||
			$formField->fieldType == 'oclcNumber' ||
			$formField->fieldType == 'articleInfo' ||
			$formField->fieldType == 'upc' ||
			$formField->fieldType == 'issn' ||
			$formField->fieldType == 'season'}
				{assign var="materialRequestTableColumnName" value=$formField->fieldType}
				<div class="row form-group">
					<label for="{$materialRequestTableColumnName}" class="control-label col-sm-3">{$formField->fieldLabel}: </label>
					<div class="request_detail_field_value col-sm-9">
						<input name="{$materialRequestTableColumnName}" id="{$materialRequestTableColumnName}"
										size="90" maxlength="255" class="form-control"
										value="{$materialsRequest->$materialRequestTableColumnName}">
					</div>
				</div>

				{* Text Area Fields*}
			{elseif
			$formField->fieldType == 'comments' ||
			$formField->fieldType == 'about'}

				{assign var="materialRequestTableColumnName" value=$formField->fieldType}
				<div class="row form-group">
					<label for="{$materialRequestTableColumnName}" class="control-label col-sm-3">{$formField->fieldLabel}: </label>
					<div class="request_detail_field_value col-sm-9">
							<textarea name="{$materialRequestTableColumnName}" id="{$materialRequestTableColumnName}" rows="3" cols="80"
							          class="form-control {if $formField->fieldType == 'about' && $requireAboutField} required{/if}">
								{$materialsRequest->$materialRequestTableColumnName}
							</textarea>
					</div>
				</div>

			{elseif $formField->fieldType == 'status'}
				{assign var="materialRequestTableColumnName" value=$formField->fieldType}
				<div class="request_detail_field row">
					<label for="{$materialRequestTableColumnName}" class="control-label col-sm-3">{$formField->fieldLabel}: </label>
					<div class=" request_detail_field_value col-sm-9">
						{if $isAdminUser}
							<select name="{$materialRequestTableColumnName}" id="{$materialRequestTableColumnName}" class="form-control">
								{foreach from=$availableStatuses item=statusLabel key=status}
									<option value="{$status}"{if $materialsRequest->status == status} selected="selected"{/if}>{$statusLabel}</option>
								{/foreach}
							</select>
						{else}
							{$materialsRequest->statusLabel}
						{/if}
					</div>
				</div>

			{elseif
			$formField->fieldType == 'dateCreated'||
			$formField->fieldType == 'dateUpdated'}
				{* Date Fields *}
				{assign var="materialRequestTableColumnName" value=$formField->fieldType}
				<div class="request_detail_field row">
					<label class="control-label col-sm-3">{$formField->fieldLabel}: </label>
					<div class="request_detail_field_value col-sm-9">
						{$materialsRequest->$materialRequestTableColumnName|date_format}
					</div>
				</div>

			{elseif $formField->fieldType == 'emailSent' ||
			$formField->fieldType == 'holdsCreated'}
				{* Yes / No Fields *}
				{assign var="materialRequestTableColumnName" value=$formField->fieldType}
				<div class="row form-group">
					<label class="control-label col-sm-3">{$formField->fieldLabel}: </label>
					<div class="request_detail_field_value col-sm-9">

						<label for="{$materialRequestTableColumnName}Yes" class="radio-inline">
							<input type="radio" name="{$materialRequestTableColumnName}" value="1" id="{$materialRequestTableColumnName}Yes"{if $materialsRequest->$materialRequestTableColumnName == 1} checked="checked"{/if}>Yes
						</label>
						&nbsp;&nbsp;
						<label for="{$materialRequestTableColumnName}No" class="radio-inline">
							<input type="radio" name="{$materialRequestTableColumnName}" value="0" id="{$materialRequestTableColumnName}No"{if $materialsRequest->$materialRequestTableColumnName == 0} checked="checked"{/if}>No
						</label>

					</div>
				</div>

				{* USER INFORMATION FIELDS  *}

			{elseif $formField->fieldType == 'createdBy'}
				{if $showUserInformation}
					<div class="request_detail_field row">
						<label class="control-label col-sm-3">{$formField->fieldLabel}: </label>
						<div class="request_detail_field_value col-sm-9">
							{$requestUser->firstname} {$requestUser->lastname}
						</div>
					</div>
				{/if}


				{* Regular User Input Field *}
			{elseif
			$formField->fieldType == 'phone' ||
			$formField->fieldType == 'email'}
				{if $showUserInformation || $new}
					{assign var="materialRequestTableColumnName" value=$formField->fieldType}
					<div class="row form-group">
						<label for="{$materialRequestTableColumnName}" class="control-label col-sm-3">{$formField->fieldLabel}: </label>
						<div class="request_detail_field_value col-sm-9">
							<input name="{$materialRequestTableColumnName}" id="{$materialRequestTableColumnName}"
											size="90" maxlength="255" class="form-control"
											value="{$materialsRequest->$materialRequestTableColumnName}">
						</div>
					</div>
				{/if}

			{elseif
			$formField->fieldType == 'illItem' ||
			$formField->fieldType == 'placeHoldWhenAvailable'}
				{* Yes / No  User Information Fields *}
				{if $showUserInformation || $new}
					{assign var="materialRequestTableColumnName" value=$formField->fieldType}
					<div class="row form-group ebookHideField eaudioHideField specialFormatHideField"{if $formField->fieldType == 'illItem'} id="illInfo"{/if}>
						<label class="control-label col-sm-3">{$formField->fieldLabel}: </label>
						<div class="request_detail_field_value col-sm-9">

							<label for="{$materialRequestTableColumnName}Yes" class="radio-inline">
								<input type="radio" name="{$materialRequestTableColumnName}" value="1" id="{$materialRequestTableColumnName}Yes"{if $materialsRequest->$materialRequestTableColumnName == 1} checked="checked"{/if} onchange="VuFind.MaterialsRequest.updateHoldOptions()">Yes
							</label>
							&nbsp;&nbsp;
							<label for="{$materialRequestTableColumnName}No" class="radio-inline">
								<input type="radio" name="{$materialRequestTableColumnName}" value="0" id="{$materialRequestTableColumnName}No"{if $materialsRequest->$materialRequestTableColumnName == 0} checked="checked"{/if} onchange="VuFind.MaterialsRequest.updateHoldOptions()">No
							</label>

						</div>
					</div>
				{/if}

			{elseif $formField->fieldType == 'holdPickupLocation'}
				{if $showUserInformation || $new} {* Not shown till placeHoldWhenAvailable is set to yes. *}
					<div id="pickupLocationField" class="row form-group ebookHideField eaudioHideField" style="display: none">
						<label for="pickupLocation" class="control-label col-sm-3">{$formField->fieldLabel}: </label>
						<div class=" request_detail_field_value col-sm-9">
							<select name="holdPickupLocation" id="pickupLocation" onchange="VuFind.MaterialsRequest.updateHoldOptions();" class="form-control">
								{foreach from=$pickupLocations item=location}
									<option value="{$location.id}" {if $location.selected}selected="selected"{/if}>{$location.displayName}</option>
								{/foreach}
							</select>
						</div>
					</div>
				{/if}
			{elseif $formField->fieldType == 'bookmobileStop'}
				{if $showUserInformation || $new}
					{assign var="materialRequestTableColumnName" value=$formField->fieldType}
					{* Book Mobile Stop Field should be hidden by default, gets shown when holdPickUpLocation is set to bookmobile (done by VuFind.MaterialsRequest.updateHoldOptions() *}
					<div id="bookmobileStopField" class="row form-group ebookHideField eaudioHideField" style="display: none">
						<label for="{$materialRequestTableColumnName}" class="control-label col-sm-3">{$formField->fieldLabel}: </label>
						<div class="col-sm-9">
							<input name="{$materialRequestTableColumnName}" id="{$materialRequestTableColumnName}" size="50" maxlength="50" class="form-control" value="{$materialsRequest->$materialRequestTableColumnName}">
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

				{* End of User Information Fields *}

			{elseif $formField->fieldType == 'ageLevel'}
				{assign var="materialRequestTableColumnName" value=$formField->fieldType}
				<div class="row form-group">
					<label class="control-label col-sm-3">{$formField->fieldLabel}: </label>
					<div class="request_detail_field_value col-sm-9">
						<select name="ageLevel" id="ageLevel" class="form-control">
							<option value="adult" {if $materialsRequest->ageLevel=='adult'}selected='selected'{/if}>Adult</option>
							<option value="teen" {if $materialsRequest->ageLevel=='teen'}selected='selected'{/if}>Teen</option>
							<option value="children" {if $materialsRequest->ageLevel=='children'}selected='selected'{/if}>Children</option>
							<option value="unknown" {if !isset($materialsRequest->ageLevel) || $materialsRequest->ageLevel=='unknown'}selected='selected'{/if}>Don't Know</option>
						</select>
					</div>
				</div>

			{/if}

		{/foreach}
	</fieldset>
{/foreach}
		{* Make Sure Id is always included when set, even if it isn't displayed *}
		{if !$hasId && !empty($materialsRequest->id)}
			<input type="hidden" name="id" id="id" value="{$materialsRequest->id}">
		{/if}
</div>
{/strip}

