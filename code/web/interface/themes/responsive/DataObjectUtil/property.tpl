{assign var=propName value=$property.property}
{assign var=propValue value=$object->$propName}
{if !isset($propValue) && isset($property.default)}
	{assign var=propValue value=$property.default}
{/if}
{if ((!isset($property.storeDb) || $property.storeDb == true) && !($property.type == 'oneToManyAssociation' || $property.type == 'hidden' || $property.type == 'method'))}
	<div class="form-group" id="propertyRow{$propName}">
		{* Output the label *}
		{if $property.type == 'enum'}
			<label for='{$propName}Select'{if $property.description} title="{$property.description}"{/if}>{$property.label}{if $property.required}<span class="required-input">*</span>{/if}</label>
		{elseif $property.type == 'oneToMany' && !empty($property.helpLink)}
			<div class="row">
			<div class="col-xs-11">
				<label for='{$propName}'{if $property.description} title="{$property.description}"{/if}>{$property.label}</label>
			</div>
			<div class="col-xs-1">
				<a href="{$property.helpLink}" target="_blank"><img src="{$path}/interface/themes/responsive/images/help.png" alt="Help"></a>
			</div>
			</div>
		{elseif $property.type != 'section' && $property.type != 'checkbox'}
			<label for='{$propName}'{if $property.description} title="{$property.description}"{/if}>{$property.label}{if $property.required}<span class="required-input">*</span>{/if}</label>
		{/if}
		{* Output the editing control*}
		{if $property.type == 'section'}
			<div class="panel-group" id="accordion_{$property.label|escapeCSS}">
				<div class="panel panel-default">
					<div class="panel-heading row">
						<h4 class="panel-title col-xs-11">
							<a data-toggle="collapse" data-parent="#accordion_{$property.label|escapeCSS}" href="#accordion_body_{$property.label|escapeCSS}">
								{$property.label}
							</a>
						</h4>
						{if $property.helpLink}
							<div class="col-xs-1">
								<a href="{$property.helpLink}" target="_blank"><img src="{$path}/interface/themes/responsive/images/help.png" alt="Help"></a>
							</div>
						{/if}
					</div>

					<div id="accordion_body_{$property.label|escapeCSS}" class="panel-collapse collapse">
						<div class="panel-body">
							{if $property.instructions}
								<div class="alert alert-info">
									{$property.instructions}
								</div>
							{/if}
							{foreach from=$property.properties item=property}
								{include file="DataObjectUtil/property.tpl"}
							{/foreach}
						</div>
					</div>
				</div>
			</div>
		{elseif $property.type == 'text' || $property.type == 'folder'}
			<input type='text' name='{$propName}' id='{$propName}' value='{$propValue|escape}' {if $property.maxLength}maxlength='{$property.maxLength}'{/if} {if $property.size}size='{$property.size}'{/if} class='form-control {if $property.required}required{/if}'>
		{elseif $property.type == 'integer'}
			<input type='number' name='{$propName}' id='{$propName}' value='{$propValue|escape}' {if $property.max}max="{$property.max}"{/if} {if $property.min}min="{$property.min}"{/if} {if $property.maxLength}maxlength='{$property.maxLength}'{/if} {if $property.size}size='{$property.size}'{/if} class='form-control {if $property.required}required{/if}'>
		{elseif $property.type == 'url'}
			<input type='text' name='{$propName}' id='{$propName}' value='{$propValue|escape}' {if $property.maxLength}maxlength='{$property.maxLength}'{/if} {if $property.size}size='{$property.size}'{/if} class='form-control url {if $property.required}required{/if}'>
		{elseif $property.type == 'email'}
			<input type='text' name='{$propName}' id='{$propName}' value='{$propValue|escape}' {if $property.maxLength}maxlength='{$property.maxLength}'{/if} {if $property.size}size='{$property.size}'{/if} class='form-control email {if $property.required}required{/if}'>
		{elseif $property.type == 'color'}
			<div class="row">
				<div class="col-sm-3">
					<input type='color' name='{$propName}' id='{$propName}' value='{$propValue|escape}' class='form-control{if $property.required}required{/if}' size="7" maxlength="7" onchange="$('#{$propName}Hex').value(this.value)">
				</div>
				<div class="col-sm-3">
					<input type='text' id='{$propName}Hex' value='{$propValue|escape}' class='form-control' size="7" maxlength="7" onchange="$('#{$propName}').val(this.value)" pattern="^#([a-fA-F0-9]{ldelim}6{rdelim})$">
				</div>
				<div class="col-sm-6">
					{assign var=defaultVariableName value="`$propName`Default"}
					{if is_null($object->$defaultVariableName)}
						{assign var=useDefault value=true}
					{else}
						{assign var=useDefault value=$object->$defaultVariableName}
					{/if}

					<input type="checkbox" name='{$propName}-default' id='{$propName}-default' {if $useDefault == '1'}checked="checked"{/if}/><label for='{$propName}-default'>Use Default</label>
				</div>
			</div>
		{elseif $property.type == 'multiemail'}
			<input type='text' name='{$propName}' id='{$propName}' value='{$propValue|escape}' {if $property.maxLength}maxlength='{$property.maxLength}'{/if} {if $property.size}size='{$property.size}'{/if} class='form-control multiemail {if $property.required}required{/if}'>
		{elseif $property.type == 'date'}
			{*<input type='{$property.type}' name='{$propName}' id='{$propName}' value='{$propValue}' {if $property.maxLength}maxLength='10'{/if}	class='form-control {if $property.required}required{/if} date'>*}
			{* disable html5 features until consistly implemented *}
			{*<input type='text' name='{$propName}' id='{$propName}' value='{$propValue}' {if $property.maxLength}maxLength='10'{/if}	class='form-control {if $property.required}required{/if} date'>*}
			<input type='text' name='{$propName}' id='{$propName}' value='{$propValue}' {if $property.maxLength}maxLength='10'{/if}	class='form-control {if $property.required}required{/if} datePika'>
			{* datePika is for the form validator *}
		{elseif $property.type == 'partialDate'}
			{include file="DataObjectUtil/partialDate.tpl"}

		{elseif $property.type == 'textarea' || $property.type == 'html' || $property.type == 'crSeparated'}
			{include file="DataObjectUtil/textarea.tpl"}

		{elseif $property.type == 'password'}
			{include file="DataObjectUtil/password.tpl"}

		{elseif $property.type == 'pin'}
			<input type='password' name='{$propName}' id='{$propName}' value='{$propValue|escape}' {if $property.maxLength}maxlength='{$property.maxLength}'{/if} {if $property.size}size='{$property.size}'{/if} class='form-control digits {if $property.required}required{/if}'>


		{elseif $property.type == 'currency'}
			{include file="DataObjectUtil/currency.tpl"}

		{elseif $property.type == 'label'}
			<div id='{$propName}'>{$propValue}</div>

		{*{elseif $property.type == 'html'}*}
			{*{include file="DataObjectUtil/htmlField.tpl"}*}

		{elseif $property.type == 'enum'}
			{include file="DataObjectUtil/enum.tpl"}

		{elseif $property.type == 'multiSelect'}
			{include file="DataObjectUtil/multiSelect.tpl"}

		{elseif $property.type == 'image' || $property.type == 'file'}
			{if $propValue}
				{if $property.type == 'image'}
					{if $property.thumbWidth}
						<img src='{$path}/files/thumbnail/{$propValue}' style="display: block">
						{$propValue} &nbsp;
					{else}
						<img src='{$path}/files/original/{$propValue}' style="display: block">
						{$propValue} &nbsp;
					{/if}
					<input type='checkbox' name='remove{$propName}' id='remove{$propName}'> Remove image.
					<br>
				{else}
					Existing file: {$propValue}
					<input type='hidden' name='{$propName}_existing' id='{$propName}_existing' value='{$propValue|escape}'>

				{/if}
			{/if}
			{* Display a table of the association with the ability to add and edit new values *}
			<input type="file" name='{$propName}' id='{$propName}' size="80">
		{elseif $property.type == 'checkbox'}
			<div class="checkbox">
				<label for='{$propName}'{if $property.description} title="{$property.description}"{/if}>
					<input type='checkbox' name='{$propName}' id='{$propName}' {if ($propValue == 1)}checked='checked'{/if}> {$property.label}
				</label>
			</div>

		{elseif $property.type == 'oneToMany'}
			{include file="DataObjectUtil/oneToMany.tpl"}
		{/if}

	</div>
{elseif $property.type == 'hidden'}
	<input type='hidden' name='{$propName}' value='{$propValue}'>
{/if}
{if $property.showDescription}
	<div class='propertyDescription'>{$property.description}</div>
{/if}