{assign var=propName value=$property.property}
{if $property.type != 'section'}
	{* Note, you cannot combine both a provided object with loading from property defaults. *}
	{if !empty($object)}
		{assign var=propValue value=$object->$propName}
		{assign var=objectId value=$object->getPrimaryKeyValue()}
	{else}
		{if isset($property.default)}
			{assign var=propValue value=$property.default}
		{else}
			{assign var=propValue value=""}
		{/if}
	{/if}
{else}
	{assign var=propValue value=""}
{/if}
{strip}
{if ((!isset($property.storeDb) || $property.storeDb == true) && !($property.type == 'oneToManyAssociation' || $property.type == 'hidden' || $property.type == 'method'))}
	<div {if !isset($addFormGroupToProperty) || $addFormGroupToProperty !== false}class="form-group propertyRow"{/if} id="propertyRow{$propName}" {if !empty($property.hiddenByDefault) && $property.hiddenByDefault}style="display:none" {/if} {if !empty($property.relatedIls)}data-related-ils="~{implode subject=$property.relatedIls glue='~'}~"{/if}>
		{* Output the label *}
		{if $property.type == 'enum'}
			{if !empty($property.renderAsHeading)}
				{if !empty($property.required)}
					<p style="margin-bottom: .5em">
						<p class="{if !empty($property.headingLevel)}{$property.headingLevel}{else}h2{/if}" style="display: inline; vertical-align: top; margin-right: .25em" {if !empty($property.description)}aria-describedby="{$property.property}Tooltip" {/if}>{translate text=$property.label isAdminFacing=true}</p>
						{include file="DataObjectUtil/fieldLockingInfo.tpl"}
						{if !empty($property.description)}
							<a style="margin-right: .5em; margin-left: .25em; display: inline;" id="{$property.property}Tooltip" class="text-info" role="tooltip" tabindex="0" data-toggle="tooltip" data-placement="right" data-title="{translate text=$property.description isAdminFacing=true inAttribute=true}">
								<i class="fas fa-question-circle" style="vertical-align: top"></i>
							</a>
						{/if}
						<span class="label label-danger" style="margin-right: .5em;{if empty($property.description)}margin-left: .5em;{/if} vertical-align: top">{translate text="Required" isAdminFacing=true}</span>
					</div>
				{else}
					<p class="{if !empty($property.headingLevel)}{$property.headingLevel}{else}h2{/if}" style="display: inline;" {if !empty($property.description)}aria-describedby="{$property.property}Tooltip"{/if}>{translate text=$property.label isAdminFacing=true}</p>
					{if !empty($property.description)}
						<a style="margin-right: .5em; margin-left: .25em; display: inline;" id="{$property.property}Tooltip" class="text-info" role="tooltip" tabindex="0" data-toggle="tooltip" data-placement="right" data-title="{translate text=$property.description isAdminFacing=true inAttribute=true}">
							<i class="fas fa-question-circle" style="vertical-align: top"></i>
						</a>
					{/if}
					{include file="DataObjectUtil/fieldLockingInfo.tpl"}
					{if !empty($property.required)}
						<span class="label label-danger" style="margin-right: .5em{if empty($property.description)}; margin-left: .5em{/if}">{translate text="Required" isAdminFacing=true}</span>
					{/if}
				{/if}
			{else}
				<label for='{$propName}Select' {if !empty($property.description)}aria-describedby="{$propName}Tooltip"{/if}>
					{translate text=$property.label isAdminFacing=true}
				</label>
				{if !empty($property.description)}<a style="margin-right: .5em; margin-left: .25em" id="{$propName}Tooltip" class="text-info" role="tooltip" tabindex="0" data-toggle="tooltip" data-placement="right" data-title="{translate text=$property.description isAdminFacing=true inAttribute=true}"><i class="fas fa-question-circle"></i></a>{/if}
				{include file="DataObjectUtil/fieldLockingInfo.tpl"}
				{if !empty($property.required)}
					<span class="label label-danger" style="margin-right: .5em{if empty($property.description)};margin-left: .5em;{/if}">{translate text="Required" isAdminFacing=true}</span>
				{/if}
			{/if}
		{elseif $property.type == 'oneToMany' && !empty($property.helpLink)}
			<div class="row">
				<div class="col-xs-11">
				{if !empty($property.renderAsHeading)}
					{if !empty($property.required)}
						<div style="margin-bottom: .5em">
							<p class="{if !empty($property.headingLevel)}{$property.headingLevel}{else}h2{/if}" style="display: inline; vertical-align: top; margin-right: .25em" {if !empty($property.description)}aria-describedby="{$property.property}Tooltip"{/if}>{translate text=$property.label isAdminFacing=true}</p>
							{include file="DataObjectUtil/fieldLockingInfo.tpl"}
							{if !empty($property.description)}
								<a style="margin-right: .5em; margin-left: .25em; display: inline;" id="{$property.property}Tooltip" class="text-info" role="tooltip" tabindex="0" data-toggle="tooltip" data-placement="right" data-title="{translate text=$property.description isAdminFacing=true inAttribute=true}">
									<i class="fas fa-question-circle" style="vertical-align: top"></i>
								</a>
							{/if}
							<span class="label label-danger" style="margin-right: .5em;{if empty($property.description)}margin-left: .5em;{/if} vertical-align: top">{translate text="Required" isAdminFacing=true}</span>
						</div>
					{else}
						<p class="{if !empty($property.headingLevel)}{$property.headingLevel}{else}h2{/if}" style="display: inline;" {if !empty($property.description)}aria-describedby="{$property.property}Tooltip" {/if}>{translate text=$property.label isAdminFacing=true}</p>
						{include file="DataObjectUtil/fieldLockingInfo.tpl"}
						{if !empty($property.description)}
							<a style="margin-right: .5em; margin-left: .25em; display: inline;" id="{$property.property}Tooltip" class="text-info" role="tooltip" tabindex="0" data-toggle="tooltip" data-placement="right" data-title="{translate text=$property.description isAdminFacing=true inAttribute=true}">
								<i class="fas fa-question-circle" style="vertical-align: top"></i>
							</a>
						{/if}

						{if !empty($property.required)}
							<span class="label label-danger" style="margin-right: .5em{if empty($property.description)};margin-left: .5em;{/if}">{translate text="Required" isAdminFacing=true}</span>
						{/if}
					{/if}
				{else}
					<label for='{$propName}' {if !empty($property.description)}aria-describedby="{$property.property}Tooltip" {/if}>
						{translate text=$property.label isAdminFacing=true}
					</label>
					{if !empty($property.description)}<a style="margin-right: .5em; margin-left: .25em" id="{$property.property}Tooltip" class="text-info" role="tooltip" tabindex="0" data-toggle="tooltip" data-placement="right" data-title="{translate text=$property.description isAdminFacing=true inAttribute=true}"><i class="fas fa-question-circle"></i></a>{/if}
					{include file="DataObjectUtil/fieldLockingInfo.tpl"}
					{if !empty($property.required)}
						<span class="label label-danger" style="margin-right: .5em{if empty($property.description)};margin-left: .5em;{/if}">{translate text="Required" isAdminFacing=true}</span>
					{/if}
				{/if}
				</div>
				<div class="col-xs-1">
					<a href="{$property.helpLink}" target="_blank"><img src="/interface/themes/responsive/images/help.png" alt="Help"></a>
				</div>
			</div>
		{elseif $property.type != 'section' && $property.type != 'checkbox' && $property.type != 'hidden' && $property.type != 'alert' }
			{if !empty($property.renderAsHeading)}
				{if !empty($property.required)}
					<div style="margin-bottom: .5em; {if !empty($property.showBottomBorder)}border-bottom: 2px solid {$secondaryBackgroundColor}{/if}">
						<p class="{if !empty($property.headingLevel)}{$property.headingLevel}{else}h2{/if}" style="display: inline; vertical-align: top; margin-right: .25em" {if !empty($property.description)}aria-describedby="{$property.property}Tooltip" {/if}>{translate text=$property.label isAdminFacing=true}</p>
						{if !empty($property.description)}
							<a style="margin-right: .5em; margin-left: .25em; display: inline;" id="{$property.property}Tooltip" class="text-info" role="tooltip" tabindex="0" data-toggle="tooltip" data-placement="right" data-title="{translate text=$property.description isAdminFacing=true inAttribute=true}">
								<i class="fas fa-question-circle" style="vertical-align: top"></i>
							</a>
						{/if}
						{include file="DataObjectUtil/fieldLockingInfo.tpl"}
						<span class="label label-danger" style="margin-right: .5em; vertical-align: top{if empty($property.description)};margin-left: .5em;{/if}">{translate text="Required" isAdminFacing=true}</span>
					</div>
				{else}
					<div style="margin-bottom: .5em; {if !empty($property.showBottomBorder)}border-bottom: 2px solid {$secondaryBackgroundColor}{/if}">
						<p class="{if !empty($property.headingLevel)}{$property.headingLevel}{else}h2{/if}" style="display: inline;" {if !empty($property.description)}aria-describedby="{$property.property}Tooltip" {/if}>{translate text=$property.label isAdminFacing=true}</p>
						{if !empty($property.description)}
							<a style="margin-right: .5em; margin-left: .25em; display: inline;" class="text-info" id="{$property.property}Tooltip" role="tooltip" tabindex="0" data-toggle="tooltip" data-placement="right" data-title="{translate text=$property.description isAdminFacing=true inAttribute=true}">
								<i class="fas fa-question-circle" style="vertical-align: top"></i>
							</a>
						{/if}
						{include file="DataObjectUtil/fieldLockingInfo.tpl"}
						{if !empty($property.required)}
							<span class="label label-danger" style="margin-right: .5em{if empty($property.description)};margin-left: .5em;{/if}">{translate text="Required" isAdminFacing=true}</span>
						{/if}
					</div>
				{/if}
			{else}
				<label for='{$propName}' {if !empty($property.description)}aria-describedby="{$propName}Tooltip" {/if}>{translate text=$property.label isAdminFacing=true}</label>
				{if !empty($property.description)}<a style="margin-right: .5em; margin-left: .25em" id="{$propName}Tooltip" class="text-info" role="tooltip" tabindex="0" data-toggle="tooltip" data-placement="right" data-title="{translate text=$property.description isAdminFacing=true inAttribute=true}"><i class="fas fa-question-circle"></i></a>{/if}
				{include file="DataObjectUtil/fieldLockingInfo.tpl"}
				{if !empty($property.required)}
					<span class="label label-danger" style="margin-right: .5em{if empty($property.description)};margin-left: .5em;{/if}">{translate text="Required" isAdminFacing=true}</span>
				{/if}
			{/if}
		{/if}
		{if !empty($property.showDescription)}
			<div class='propertyDescription'><em>{$property.description}</em></div>
		{/if}
		{* Output the editing control*}
		{if $property.type == 'section'}
			{if !empty($property.renderAsHeading)}
				<div class="row propertySectionHeading">
					<div class="col-xs-12">
						{if !empty($property.label)}
						<div style="margin-bottom: .5em; {if !empty($property.showBottomBorder)}border-bottom: 2px solid {$secondaryBackgroundColor}{/if}">
							<p class="{if !empty($property.headingLevel)}{$property.headingLevel}{else}h2{/if}" style="display: inline" {if !empty($property.description)}aria-describedby="{$property.property}Tooltip" {/if}>{translate text=$property.label isAdminFacing=true}</p>
							{if !empty($property.description)}
								<a style="margin-right: .5em; margin-left: .25em; display: inline;" id="{$property.property}Tooltip" class="text-info" role="tooltip" tabindex="0" data-toggle="tooltip" data-placement="right" data-title="{translate text=$property.description isAdminFacing=true inAttribute=true}">
									<i class="fas fa-question-circle" style="vertical-align: top"></i>
								</a>
							{/if}
							{if !empty($property.required)}
								<span class="label label-danger" style="margin-right: .5em{if empty($property.description)};margin-left: .5em;{/if}">{translate text="Required" isAdminFacing=true}</span>
							{/if}
						</div>
							{if !empty($property.note)}<span id="{$propName}HelpBlock" class="help-block"><small><i class="fas fa-info-circle"></i> {$property.note}</small></span>{/if}
						{/if}

						{foreach from=$property.properties item=property}
							{include file="DataObjectUtil/property.tpl"}
						{/foreach}
					</div>
				</div>
			{else}
				<div class="panel-group propertySection" id="accordion_{$property.label|escapeCSS}">
					<div id="panelStatus_{$property.label|escapeCSS}" class="panel panel-default {if !empty($property.expandByDefault)}active{/if}">
						<div class="panel-heading row">
							<div class="panel-title col-xs-11">
								<a id="panelToggle_{$property.property}" data-toggle="collapse" data-parent="#accordion_{$property.label|escapeCSS}" href="#accordion_body_{$property.label|escapeCSS}" aria-expanded="{if !empty($property.expandByDefault)}true{else}false{/if}" class="{if !empty($property.expandByDefault)}expanded{else}collapsed{/if}">
									{translate text=$property.label isAdminFacing=true}
								</a>
							</div>
							{if !empty($property.helpLink)}
								<div class="col-xs-1">
									<a href="{$property.helpLink}" target="_blank"><img src="/interface/themes/responsive/images/help.png" alt="Help"></a>
								</div>
							{/if}
						</div>

						<div id="accordion_body_{$property.label|escapeCSS}" class="accordion_body panel-collapse {if !empty($property.expandByDefault)}in{else}collapse{/if}" {if empty($property.expandByDefault)}style="display:none"{/if} aria-labelledby="panelToggle_{$property.property}" role="region">
							<div class="panel-body">
								{if !empty($property.instructions)}
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
				<script type="text/javascript">
					{* Initiate any checkbox with a data attribute set to data-switch=""  as a bootstrap switch *}
					{literal}
					$("#panelToggle_{/literal}{$property.property}{literal}").click(function() {
						var toggleButton = $(this);
						$(this).toggleClass('expanded');
						$(this).toggleClass('collapsed');
						var panelStatus = $("#panelStatus_{/literal}{$property.label|escapeCSS}{literal}");
						$('#accordion_body_{/literal}{$property.label|escapeCSS}{literal}').toggle()
						if (toggleButton.attr("aria-expanded") === "true") {
							$(this).attr("aria-expanded","false");
							panelStatus.removeClass("active");
						}
						else if (toggleButton.attr("aria-expanded") === "false") {
							$(this).attr("aria-expanded","true")
							panelStatus.addClass("active");
						}
						return false;
					})
					{/literal}
				</script>
			{/if}
		{elseif $property.type == 'foreignKey' && !empty($property.editLink)}
			<div class="row">
				<div class="col-sm-12">
					<a class="btn btn-default btn-sm" href="{$property.editLink|replace:'propertyValue':$propValue}">Edit {$property.label}</a>
				</div>
			</div>
		{elseif $property.type == 'text' || $property.type == 'regularExpression' || $property.type == 'folder'}
			<input type='text' name='{$propName}' id='{$propName}' value='{$propValue|escape}' {if !empty($property.accessibleLabel)}aria-label="{$property.accessibleLabel}"{/if} {if !empty($property.maxLength)}maxlength='{$property.maxLength}'{/if} {if !empty($property.size)}size='{$property.size}'{/if} class='form-control {if !empty($property.required)}required{/if}{if !empty($property.validationGroupName)} {$property.validationGroupName}-validation-group{/if}' {if !empty($property.autocomplete)}autocomplete="{$property.autocomplete}"{/if} {if !empty($property.readOnly)}readonly{/if}  {if !empty($property.forcesReindex)}aria-describedby="{$propName}HelpBlock"{/if} >
			{if !empty($property.forcesReindex)}<span id="{$propName}HelpBlock" class="help-block" style="margin-top:0"><small class="text-warning"><i class="fas fa-exclamation-triangle"></i> {translate text="Updating this setting causes a nightly reindex" isAdminFacing=true}</small></span>{/if}
			{if !empty($property.affectsLiDA)}<span id="{$propName}HelpBlock" class="help-block" style="margin-top:0"><small class="text-info"><i class="fas fa-info-circle"></i> {translate text="Aspen LiDA also uses this setting" isAdminFacing=true}</small></span>{/if}
			{if !empty($property.note)}<span id="{$propName}HelpBlock" class="help-block" style="margin-top:0"><small><i class="fas fa-info-circle"></i> {$property.note}</small></span>{/if}
		{elseif $property.type == 'integer'}
			<input type='number' name='{$propName}' id='{$propName}' value='{$propValue|escape}' {if !empty($property.accessibleLabel)}aria-label="{$property.accessibleLabel}"{/if} {if !empty($property.max)}max="{$property.max}"{/if} {if !empty($property.min)}min="{$property.min}"{/if} {if !empty($property.maxLength)}maxlength='{$property.maxLength}'{/if} {if !empty($property.size)}size='{$property.size}'{/if} class='form-control {if !empty($property.required)}required{/if}' {if !empty($property.readOnly)}readonly{/if}{if !empty($property.onchange)} onchange="{$property.onchange}"{/if}>
			{if !empty($property.forcesReindex)}<span id="{$propName}HelpBlock" class="help-block"><small class="text-warning"><i class="fas fa-exclamation-circle"></i> {translate text="Updating this setting causes a nightly reindex" isAdminFacing=true}</small></span>{/if}
			{if !empty($property.affectsLiDA)}<span id="{$propName}HelpBlock" class="help-block"><small class="text-info"><i class="fas fa-info-circle"></i> {translate text="Aspen LiDA also uses this setting" isAdminFacing=true}</small></span>{/if}
			{if !empty($property.note)}<span id="{$propName}HelpBlock" class="help-block"><small><i class="fas fa-info-circle"></i> {$property.note}</small></span>{/if}
		{elseif $property.type == 'timestamp'}
			<div class="row">
				<div class="col-sm-4">
					<input name='{$propName}' id='{$propName}' value='{if !empty($propValue)}{$propValue|date_format:"%Y-%m-%d %H:%M"}{/if}' {if !empty($property.accessibleLabel)}aria-label="{$property.accessibleLabel}"{/if} {if !empty($property.max)}max="{$property.max}"{/if} {if !empty($property.min)}min="{$property.min}"{/if} {if !empty($property.maxLength)}maxlength='{$property.maxLength}'{/if} {if !empty($property.size)}size='{$property.size}'{/if} class='form-control {if !empty($property.required)}required{/if}' {if !empty($property.readOnly)}readonly disabled{/if}>
				</div>
				{if empty($property.readOnly)}
					<script type="text/javascript">
						$(document).ready(function(){ldelim}
							rome({$propName});
						{rdelim});
					</script>
				{/if}
			</div>
			{if !empty($property.forcesReindex)}<span id="{$propName}HelpBlock" class="help-block" style="margin-top:0"><small class="text-warning"><i class="fas fa-exclamation-triangle"></i> {translate text="Updating this setting causes a nightly reindex" isAdminFacing=true}</small></span>{/if}
			{if !empty($property.affectsLiDA)}<span id="{$propName}HelpBlock" class="help-block" style="margin-top:0"><small class="text-info"><i class="fas fa-info-circle"></i> {translate text="Aspen LiDA also uses this setting" isAdminFacing=true}</small></span>{/if}
			{if !empty($property.note)}<span id="{$propName}HelpBlock" class="help-block" style="margin-top:0"><small><i class="fas fa-info-circle"></i> {$property.note}</small></span>{/if}
		{elseif $property.type == 'url'}
			<input type='text' name='{$propName}' id='{$propName}' value='{$propValue|escape}' {if !empty($property.accessibleLabel)}aria-label="{$property.accessibleLabel}"{/if} {if !empty($property.maxLength)}maxlength='{$property.maxLength}'{/if} {if !empty($property.size)}size='{$property.size}'{/if} class='form-control url {if !empty($property.required)}required{/if}' {if !empty($property.readOnly)}readonly{/if}>
			{if !empty($property.forcesReindex)}<span id="{$propName}HelpBlock" class="help-block" style="margin-top:0"><small class="text-warning"><i class="fas fa-exclamation-triangle"></i> {translate text="Updating this setting causes a nightly reindex" isAdminFacing=true}</small></span>{/if}
			{if !empty($property.affectsLiDA)}<span id="{$propName}HelpBlock" class="help-block" style="margin-top:0"><small class="text-info"><i class="fas fa-info-circle"></i> {translate text="Aspen LiDA also uses this setting" isAdminFacing=true}</small></span>{/if}
			{if !empty($property.note)}<span id="{$propName}HelpBlock" class="help-block" style="margin-top:0"><small><i class="fas fa-info-circle"></i> {$property.note}</small></span>{/if}
		{elseif $property.type == 'email'}
			<input type='text' name='{$propName}' id='{$propName}' value='{$propValue|escape}' {if !empty($property.accessibleLabel)}aria-label="{$property.accessibleLabel}"{/if} {if !empty($property.maxLength)}maxlength='{$property.maxLength}'{/if} {if !empty($property.size)}size='{$property.size}'{/if} class='form-control email {if !empty($property.required)}required{/if}' {if !empty($property.readOnly)}readonly{/if} {if !empty($property.autocomplete)}autocomplete="{$property.autocomplete}"{/if}>
			{if !empty($property.forcesReindex)}<span id="{$propName}HelpBlock" class="help-block" style="margin-top:0"><small class="text-warning"><i class="fas fa-exclamation-triangle"></i> {translate text="Updating this setting causes a nightly reindex" isAdminFacing=true}</small></span>{/if}
			{if !empty($property.affectsLiDA)}<span id="{$propName}HelpBlock" class="help-block" style="margin-top:0"><small class="text-info"><i class="fas fa-info-circle"></i> {$property.affectsLiDA}</small></span>{/if}
			{if !empty($property.note)}<span id="{$propName}HelpBlock" class="help-block" style="margin-top:0"><small><i class="fas fa-info-circle"></i> {translate text="Aspen LiDA also uses this setting" isAdminFacing=true}</small></span>{/if}
		{elseif $property.type == 'email2'}
			<input type='text' name='{$propName}' id='{$propName}' value='{$propValue|escape}' {if !empty($property.accessibleLabel)}aria-label="{$property.accessibleLabel}"{/if} {if !empty($property.maxLength)}maxlength='{$property.maxLength}'{/if} {if !empty($property.size)}size='{$property.size}'{/if} class='form-control email2 {if !empty($property.required)}required{/if}' {if !empty($property.readOnly)}readonly{/if} {if !empty($property.autocomplete)}autocomplete="{$property.autocomplete}"{/if}>
		{elseif  $property.type == 'email_prefill'}
			<input type='text' name='{$propName}' id='{$propName}' value='{if !empty($user)}{if !empty({$user->email})}{$user->email}{/if}{/if}' {if !empty($property.accessibleLabel)}aria-label="{$property.accessibleLabel}"{/if} {if !empty($property.maxLength)}maxlength='{$property.maxLength}'{/if} {if !empty($property.size)}size='{$property.size}'{/if} class='form-control email {if !empty($property.required)}required{/if}' {if !empty($property.readOnly)}readonly{/if} {if !empty($property.autocomplete)}autocomplete="{$property.autocomplete}"{/if}>
		{elseif  $property.type == 'barcode_prefill'}
			<input type='text' name='{$propName}' id='{$propName}' value='{if !empty($user)}{if !empty({$user->getBarcode()})}{$user->getBarcode()}{/if}{/if}' {if !empty($property.accessibleLabel)}aria-label="{$property.accessibleLabel}"{/if} {if !empty($property.maxLength)}maxlength='{$property.maxLength}'{/if} {if !empty($property.size)}size='{$property.size}'{/if} class='form-control {if !empty($property.required)}required{/if}' {if !empty($property.readOnly)}readonly{/if} {if !empty($property.autocomplete)}autocomplete="{$property.autocomplete}"{/if}>
		{elseif  $property.type == 'name_prefill'}
			<input type='text' name='{$propName}' id='{$propName}' value='{if !empty($user)}{if !empty({$user->firstname}) && !empty({$user->lastname})}{$user->firstname|escape} {$user->lastname|escape}{elseif !empty({$user->firstname})}{$user->firstname|escape}{elseif !empty({$user->lastname})}{$user->lastname|escape}{/if}{/if}' {if !empty($property.accessibleLabel)}aria-label="{$property.accessibleLabel}"{/if} {if !empty($property.maxLength)}maxlength='{$property.maxLength}'{/if} {if !empty($property.size)}size='{$property.size}'{/if} class='form-control {if !empty($property.required)}required{/if}' {if !empty($property.readOnly)}readonly{/if} {if !empty($property.autocomplete)}autocomplete="{$property.autocomplete}"{/if}>
		{elseif  $property.type == 'phone_prefill'}
			<input type='text' name='{$propName}' id='{$propName}' value='{if !empty($user)}{if !empty({$user->phone})}{$user->phone}{/if}{/if}' {if !empty($property.accessibleLabel)}aria-label="{$property.accessibleLabel}"{/if} {if !empty($property.maxLength)}maxlength='{$property.maxLength}'{/if} {if !empty($property.size)}size='{$property.size}'{/if} class='form-control {if !empty($property.required)}required{/if}' {if !empty($property.readOnly)}readonly{/if} {if !empty($property.autocomplete)}autocomplete="{$property.autocomplete}"{/if}>
		{elseif  $property.type == 'address_prefill'}
			<input type='text' name='{$propName}' id='{$propName}' value='{if !empty($user)}{if !empty({$user->_address1})}{$user->_address1}{/if}{/if}' {if !empty($property.accessibleLabel)}aria-label="{$property.accessibleLabel}"{/if} {if !empty($property.maxLength)}maxlength='{$property.maxLength}'{/if} {if !empty($property.size)}size='{$property.size}'{/if} class='form-control {if !empty($property.required)}required{/if}' {if !empty($property.readOnly)}readonly{/if} {if !empty($property.autocomplete)}autocomplete="{$property.autocomplete}"{/if}>
		{elseif  $property.type == 'address2_prefill'}
			<input type='text' name='{$propName}' id='{$propName}' value='{if !empty($user)}{if !empty({$user->_address2})}{$user->_address2}{/if}{/if}' {if !empty($property.accessibleLabel)}aria-label="{$property.accessibleLabel}"{/if} {if !empty($property.maxLength)}maxlength='{$property.maxLength}'{/if} {if !empty($property.size)}size='{$property.size}'{/if} class='form-control {if !empty($property.required)}required{/if}' {if !empty($property.readOnly)}readonly{/if} {if !empty($property.autocomplete)}autocomplete="{$property.autocomplete}"{/if}>
		{elseif  $property.type == 'city_prefill'}
			<input type='text' name='{$propName}' id='{$propName}' value='{if !empty($user)}{if !empty({$user->_city})}{$user->_city}{/if}{/if}' {if !empty($property.accessibleLabel)}aria-label="{$property.accessibleLabel}"{/if} {if !empty($property.maxLength)}maxlength='{$property.maxLength}'{/if} {if !empty($property.size)}size='{$property.size}'{/if} class='form-control {if !empty($property.required)}required{/if}' {if !empty($property.readOnly)}readonly{/if} {if !empty($property.autocomplete)}autocomplete="{$property.autocomplete}"{/if}>
		{elseif  $property.type == 'state_prefill'}
			<input type='text' name='{$propName}' id='{$propName}' value='{if !empty($user)}{if !empty({$user->_state})}{$user->_state}{/if}{/if}' {if !empty($property.accessibleLabel)}aria-label="{$property.accessibleLabel}"{/if} {if !empty($property.maxLength)}maxlength='{$property.maxLength}'{/if} {if !empty($property.size)}size='{$property.size}'{/if} class='form-control {if !empty($property.required)}required{/if}' {if !empty($property.readOnly)}readonly{/if} {if !empty($property.autocomplete)}autocomplete="{$property.autocomplete}"{/if}>
		{elseif  $property.type == 'zip_prefill'}
			<input type='text' name='{$propName}' id='{$propName}' value='{if !empty($user)}{if !empty({$user->_zip})}{$user->_zip}{/if}{/if}' {if !empty($property.accessibleLabel)}aria-label="{$property.accessibleLabel}"{/if} {if !empty($property.maxLength)}maxlength='{$property.maxLength}'{/if} {if !empty($property.size)}size='{$property.size}'{/if} class='form-control {if !empty($property.required)}required{/if}' {if !empty($property.readOnly)}readonly{/if} {if !empty($property.autocomplete)}autocomplete="{$property.autocomplete}"{/if}>
		{elseif $property.type == 'color'}
			{assign var=defaultVariableName value="`$propName`Default"}
			{if is_null($object->$defaultVariableName)}
				{assign var=useDefault value=true}
			{else}
				{assign var=useDefault value=$object->$defaultVariableName}
			{/if}
			<div class="row">
				<div class="col-tn-3">
					<input type='color' name='{$propName}' id='{$propName}' value='{if $useDefault == '1'}{$property.default|escape}{else}{$propValue|escape}{/if}'  aria-label='{$property.label} color picker' class='form-control{if !empty($property.required)}required{/if}' size="7" maxlength="7" onchange="$('#{$propName}Hex').val(this.value);$('#{$propName}-default').prop('checked',false);{if !empty($property.checkContrastWith)}AspenDiscovery.Admin.checkContrast('{$propName}', '{$property.checkContrastWith}', false, '{$contrastRatio}');{/if}" {if !empty($property.readOnly)}disabled{/if}>
				</div>
				<div class="col-tn-3">
					<input type='text' id='{$propName}Hex' value='{if $useDefault == '1'}{$property.default|escape}{else}{$propValue|escape}{/if}' aria-label='{$property.label} hex code' class='form-control' size="7" maxlength="7" onchange="$('#{$propName}').val(this.value);$('#{$propName}-default').prop('checked',false);{if !empty($property.checkContrastWith)}AspenDiscovery.Admin.checkContrast('{$propName}', '{$property.checkContrastWith}', false, '{$contrastRatio}');{/if}" pattern="^#([a-fA-F0-9]{ldelim}6{rdelim})$" {if !empty($property.readOnly)}readonly{/if}>
				</div>
				<div class="col-tn-3">
					<div class="checkbox" style="margin: 0">
						<label for='{$propName}-default'>{translate text="Use Default" isAdminFacing=true}
							<input type="checkbox" name='{$propName}-default' id='{$propName}-default' {if $useDefault == '1'}checked="checked"{/if} {if !empty($property.readOnly)}readonly disabled{/if}/>
						</label>
					</div>
					<script type="text/javascript">
						$(document).ready(function () {ldelim}
							$('#{$propName}-default').change(function(){ldelim}
								if($('#{$propName}-default').is(':checked')) {ldelim}
									$('#{$propName}Hex').prop('value','{$property.default|escape}');
									$('#{$propName}').prop('value','{$property.default|escape}');
									{rdelim} else {ldelim}
									$('#{$propName}Hex').prop('value','{$propValue|escape}');
									$('#{$propName}').prop('value','{$propValue|escape}')
									{rdelim}
								{if !empty($property.checkContrastWith)}AspenDiscovery.Admin.checkContrast('{$propName}', '{$property.checkContrastWith}', false, '{$contrastRatio}');{/if}
								{rdelim})
							{rdelim});
					</script>
				</div>
				<div class="col-tn-3">
					{if !empty($property.checkContrastWith)}
						&nbsp;{translate text='Contrast Ratio' isAdminFacing=true}&nbsp;<span id="contrast_{$propName}" class="contrast_warning"></span>
						<script type="text/javascript">
							$(document).ready(function(){ldelim}
								AspenDiscovery.Admin.checkContrast('{$propName}', '{$property.checkContrastWith}'{if !empty($property.checkContrastOneWay) && $property.checkContrastOneWay==true},true{else},false{/if}, '{$contrastRatio}');
							{rdelim});
						</script>
					{/if}
				</div>
			</div>
			{assign var=fetchDefaultColor value='default|cat:$propName'}
			{if !empty($property.readOnly)}
				{literal}
					<script type="text/javascript">
						var setDefaultColor = $('#{/literal}{$propName}{literal}-default');
						$('#{/literal}{$propName}{literal}-default').on("click", function() {

							if($(this).is(":checked")) {
								$(this).attr("checked", true);
								AspenDiscovery.Admin.getDefaultColor('{/literal}{$propName}{literal}','{/literal}{$parentTheme->$propName}{literal}');
								{/literal}
								{if !empty($property.checkContrastWith)}AspenDiscovery.Admin.checkContrast('{$propName}', '{$property.checkContrastWith}', false, '{$checkContrast}');{/if}
								{literal}
							} else {
								$(this).attr("checked", false);
								document.getElementById('{/literal}{$propName}{literal}').value = '{/literal}{$propValue}{literal}';
								document.getElementById('{/literal}{$propName}{literal}Hex').value = '{/literal}{$propValue}{literal}';
								{/literal}
								{if !empty($property.checkContrastWith)}AspenDiscovery.Admin.checkContrast('{$propName}', '{$property.checkContrastWith}', false, '{$checkContrast}');{/if}
								{literal}
							}
						});
					</script>
				{/literal}
			{/if}
		{elseif $property.type == 'font'}
			<div class="row">
				<div class="col-sm-4">
					<select name='{$propName}' id='{$propName}' class='form-control font {if !empty($property.required)}required{/if}' {if !empty($property.readOnly)}readonly{/if} onchange="$('#{$propName}-default').prop('checked',false);AspenDiscovery.Admin.loadGoogleFontPreview('{$propName}')">
						{foreach from=$property.validFonts item=fontName}
							<option value="{$fontName}"{if $propValue == $fontName} selected='selected'{/if}>{$fontName}</option>
						{/foreach}
					</select>
				</div>
				<div class="col-sm-3">
					{assign var=defaultVariableName value="`$propName`Default"}
					{if is_null($object->$defaultVariableName)}
						{assign var=useDefault value=true}
					{else}
						{assign var=useDefault value=$object->$defaultVariableName}
					{/if}
					<div class="checkbox" style="margin: 0">
						<label for='{$propName}-default'>{translate text="Use Default" isAdminFacing=true}
							<input type="checkbox" name='{$propName}-default' id='{$propName}-default' {if $useDefault == '1'}checked="checked"{/if} {if !empty($property.readOnly)}readonly{/if}/>
						</label>
					</div>
				</div>
				<div class="col-sm-5">
					<div id="{$propName}-sample-text" style="font-family: {$propValue},arial; font-size: {if !empty($property.previewFontSize)}{$property.previewFontSize}{else}12px{/if}">
						English, Español, 中文(简体), עברית
					</div>
				</div>
				<script type="text/javascript">
					$().ready(function () {ldelim}
						AspenDiscovery.Admin.loadGoogleFontPreview('{$propName}');
					{rdelim});
				</script>
			</div>
		{elseif $property.type == 'uploaded_font'}
			<div class="row">
				<div class="col-sm-7">
					<div class="input-group">
						<label class="input-group-btn">
							<span class="btn btn-primary">
								{translate text="Select a font" isAdminFacing=true}&hellip; <input type="file" style="display: none;" name="{$propName}" id="{$propName}">
							</span>
						</label>
						<input type="text" class="form-control" id="importFile-label-{$propName}" readonly value="{$propValue}">
					</div>
					{if !empty($propValue)}
						<div class="checkbox" style="margin-top: 0;">
							<label for="remove{$propName}"><small class="text-danger"><i class="fas fa-trash"></i> {translate text="Remove" isAdminFacing=true}</small>
							<input type='checkbox' name='remove{$propName}' id='remove{$propName}'>
							</label>
						</div>
					{/if}
					<script type="application/javascript">
						{literal}
						$(document).on('change', '#{/literal}{$propName}{literal}:file', function() {
							var input = $(this);
							var label = input.val().replace(/\\/g, '/').replace(/.*\//, '');
							$("#importFile-label-{/literal}{$propName}{literal}").val(label);
						});
						{/literal}
					</script>
				</div>
				<div class="col-sm-5">
					<div id="{$propName}-sample-text" style="font-family: {$propValue},arial; font-size: {if !empty($property.previewFontSize)}{$property.previewFontSize}{else}12px{/if}">
						English, Español, 中文(简体), עברית
					</div>
				</div>
			</div>
		{elseif $property.type == 'multiemail'}
			<input type='text' name='{$propName}' id='{$propName}' value='{$propValue|escape}' {if !empty($property.accessibleLabel)}aria-label="{$property.accessibleLabel}"{/if} {if !empty($property.maxLength)}maxlength='{$property.maxLength}'{/if} {if !empty($property.size)}size='{$property.size}'{/if} class='form-control multiemail {if !empty($property.required)}required{/if}' {if !empty($property.readOnly)}readonly{/if}>
		{elseif $property.type == 'date'}
			<input type="date" name='{$propName}' id='{$propName}' value='{$propValue|date_format:"%Y-%m-%d"}'	class='form-control' {if !empty($property.required)}required{/if} {if !empty($property.min)}min="{$property.min}"{/if} {if !empty($property.max)}max="{$property.max}"{/if} {if !empty($property.readOnly)}readonly disabled{/if} {if !empty($property.autocomplete)}autocomplete="{$property.autocomplete}"{/if} {if !empty($property.onchange)} onchange="{$property.onchange}"{/if}>
		{elseif $property.type == 'dayMonth'}
			{include file="DataObjectUtil/dayMonthPicker.tpl"}
		{elseif $property.type == 'partialDate'}
			{include file="DataObjectUtil/partialDate.tpl"}
		{elseif $property.type == 'time'}
			<input type="time" name='{$propName}' id='{$propName}' value='{$propValue|date_format:"%H:%M"}' class='form-control' {if !empty($property.required) && (empty($objectAction) || $objectAction != 'edit')}required{/if} {if !empty($property.readOnly)}readonly disabled{/if} {if !empty($property.autocomplete)}autocomplete="{$property.autocomplete}"{/if} {if !empty($property.onchange)} onchange="{$property.onchange}"{/if}>
		{elseif $property.type == 'duration'}
			{include file="DataObjectUtil/duration.tpl"}
		{elseif $property.type == 'textarea' || $property.type == 'html' || $property.type == 'markdown' || $property.type == 'javascript' || $property.type == 'crSeparated' || $property.type == 'multilineRegularExpression'}
			{include file="DataObjectUtil/textarea.tpl"}
			{if !empty($property.forcesReindex)}<span id="{$propName}HelpBlock" class="help-block" style="margin-top:0"><small class="text-warning"><i class="fas fa-exclamation-triangle"></i> {translate text="Updating this setting causes a nightly reindex" isAdminFacing=true}</small></span>{/if}
			{if !empty($property.affectsLiDA)}<span id="{$propName}HelpBlock" class="help-block" style="margin-top:0"><small class="text-info"><i class="fas fa-info-circle"></i> {translate text="Aspen LiDA also uses this setting" isAdminFacing=true}</small></span>{/if}
			{if !empty($property.note)}<span id="{$propName}HelpBlock" class="help-block" style="margin-top:0"><small><i class="fas fa-info-circle"></i> {$property.note}</small></span>{/if}

		{elseif $property.type == 'password' || $property.type == 'storedPassword'}
			{include file="DataObjectUtil/password.tpl"}

		{elseif $property.type == 'pin'}
			<input type='password' name='{$propName}' id='{$propName}' value='{$propValue|escape}' {if !empty($property.maxLength)}maxlength='{$property.maxLength}'{/if} {if !empty($property.size)}size='{$property.size}'{/if} class='form-control pin {if !empty($property.required)}required{/if}' {if !empty($property.readOnly)}readonly{/if}>

		{elseif $property.type == 'pinConfirmation'}
			<input type='password' name='{$propName}' id='{$propName}' value='{$propValue|escape}' {if !empty($property.maxLength)}maxlength='{$property.maxLength}'{/if} {if !empty($property.size)}size='{$property.size}'{/if} class='form-control pinConfirmation {if !empty($property.required)}required{/if}' {if !empty($property.readOnly)}readonly{/if}>


		{elseif $property.type == 'currency'}
			{include file="DataObjectUtil/currency.tpl"}

		{elseif $property.type == 'label'}
			<div id='{$propName}'>
				{if empty($propValue)}
					{if empty($property.suppressNotSetForEmpty)}{translate text="Not Set" isAdminFacing=true}{/if}
				{elseif is_array($propValue)}
					{implode subject=$propValue glue=", " escape=true}
				{else}
					{$propValue|escape}
				{/if}
			</div>
			{if !empty($property.forcesReindex)}<span id="{$propName}HelpBlock" class="help-block" style="margin-top:0"><small class="text-warning"><i class="fas fa-exclamation-triangle"></i> {translate text="Updating this setting causes a nightly reindex" isAdminFacing=true}</small></span>{/if}
			{if !empty($property.affectsLiDA)}<span id="{$propName}HelpBlock" class="help-block" style="margin-top:0"><small class="text-info"><i class="fas fa-info-circle"></i> {translate text="Aspen LiDA also uses this setting" isAdminFacing=true}</small></span>{/if}
			{if !empty($property.note)}<span id="{$propName}HelpBlock" class="help-block" style="margin-top:0"><small><i class="fas fa-info-circle"></i> {$property.note}</small></span>{/if}

		{elseif $property.type == 'enum'}
			{include file="DataObjectUtil/enum.tpl"}
			{if !empty($property.forcesReindex)}<span id="{$propName}HelpBlock" class="help-block" style="margin-top:0"><small class="text-warning"><i class="fas fa-exclamation-triangle"></i> {translate text="Updating this setting causes a nightly reindex" isAdminFacing=true}</small></span>{/if}
			{if !empty($property.affectsLiDA)}<span id="{$propName}HelpBlock" class="help-block" style="margin-top:0"><small class="text-info"><i class="fas fa-info-circle"></i> {translate text="Aspen LiDA also uses this setting" isAdminFacing=true}</small></span>{/if}
			{if !empty($property.note)}<span id="{$propName}HelpBlock" class="help-block" style="margin-top:0"><small><i class="fas fa-info-circle"></i> {$property.note}</small></span>{/if}

		{elseif $property.type == 'multiSelect'}
			{include file="DataObjectUtil/multiSelect.tpl"}
			{if !empty($property.forcesReindex)}<span id="{$propName}HelpBlock" class="help-block" style="margin-top:0"><small class="text-warning"><i class="fas fa-exclamation-triangle"></i> {translate text="Updating this setting causes a nightly reindex" isAdminFacing=true}</small></span>{/if}
			{if !empty($property.affectsLiDA)}<span id="{$propName}HelpBlock" class="help-block" style="margin-top:0"><small class="text-info"><i class="fas fa-info-circle"></i> {translate text="Aspen LiDA also uses this setting" isAdminFacing=true}</small></span>{/if}
			{if !empty($property.note)}<span id="{$propName}HelpBlock" class="help-block" style="margin-top:0"><small><i class="fas fa-info-circle"></i> {$property.note}</small></span>{/if}

		{elseif $property.type == 'image' || $property.type == 'file'}
			{if !empty($propValue) && $property.type == 'image'}
				{if !empty($property.thumbWidth)}
					<img src='/files/thumbnail/{$propValue}' style="display: block" alt="Selected Image for {$property.label}">
				{else}
					{if !empty($property.displayUrl)}
						<img src='{$property.displayUrl}{$object->id}' style="display: block; max-width: 100%;" alt="Selected Image for {$property.label}">
					{else}
						<img src='/files/original/{$propValue}' style="display: block; max-width: 100%" alt="Selected Image for {$property.label}">
					{/if}
				{/if}
			{/if}
			<div class="input-group">
				<label class="input-group-btn">
					<span class="btn btn-primary">
						{if $property.type == 'image'}
							{translate text="Select an image" isAdminFacing=true}&hellip; <input type="file" style="display: none;" name="{$propName}" id="{$propName}" accept="image/jpeg, image/png, image/svg+xml" {if !empty($property.required)}required="required"{/if} {if !empty($property.readOnly)}readonly disabled{/if}>
							{literal}
							<script>
								document.getElementById('{/literal}{$propName}{literal}').addEventListener('change', function(e) {
									const allowedExtensions = /(\.jpg|\.jpeg|\.png|\.svg)$/i;
									const file = e.target.files[0];
									if (file && !allowedExtensions.exec(file.name)) {
										alert('{/literal}{translate text="Invalid file type. Allowed types: JPG, JPEG, PNG, SVG" isAdminFacing=true}{literal}');
										e.target.value = '';
									}
								});
							</script>
							{/literal}
						{else}
							{translate text="Select a file" isAdminFacing=true}&hellip; <input type="file" style="display: none;" name="{$propName}" id="{$propName}" {if !empty($property.required)}required="required"{/if} {if !empty($property.readOnly)}readonly disabled{/if}>
						{/if}
					</span>
				</label>
				<input type="text" class="form-control" id="importFile-label-{$propName}" readonly value="{$propValue}">
				<input type='hidden' name='{$propName}_existing' id='{$propName}_existing' value='{$propValue|escape}'>
			</div>
			{if !empty($propValue)}
				<div class="checkbox" style="margin-top: 0">
					<label for="remove{$propName}"><small class="text-danger"><i class="fas fa-trash"></i> {translate text="Remove" isAdminFacing=true}</small>
						<input type='checkbox' name='remove{$propName}' id='remove{$propName}' {if !empty($property.readOnly)}readonly disabled{/if}>
					</label>
				</div>
			{/if}
			{if !empty($property.note)}<span id="{$propName}HelpBlock" class="help-block" style="margin-top:0"><small><i class="fas fa-info-circle"></i> {$property.note}</small></span>{/if}
			{if !empty($property.affectsLiDA)}<span id="{$propName}HelpBlock" class="help-block" style="margin-top:0"><small class="text-info"><i class="fas fa-info-circle"></i> {translate text="Aspen LiDA also uses this setting" isAdminFacing=true}</small></span>{/if}
			<script type="application/javascript">
				{literal}
				$(document).on('change', '#{/literal}{$propName}{literal}:file', function() {
					var input = $(this);
					var label = input.val().replace(/\\/g, '/').replace(/.*\//, '');
					$("#importFile-label-{/literal}{$propName}{literal}").val(label);
				});
				{/literal}
			</script>
		{elseif $property.type == 'checkbox'}
			<div class="checkbox" {if !empty($property.forcesReindex) || !empty($property.affectsLiDA) || !empty($property.note)}style="margin-bottom: 0"{/if}>
				<label for='{$propName}' {if !empty($property.description)}aria-describedby="{$propName}Tooltip" {/if}>
					<input type='checkbox' name='{$propName}' id='{$propName}' {if ($propValue == 1)}checked='checked'{/if} {if !empty($property.readOnly)}readonly disabled{/if}{if !empty($property.onchange)} onchange="{$property.onchange}"{/if} {if !empty($property.required)}required{/if}{if !empty($property.returnValueForUnchecked) && $property.returnValueForUnchecked} onclick='$("#{$propName}Unchecked").val($(this).is(":checked") ? 1 : 0);'{/if}> {translate text=$property.label isAdminFacing=true} {if !empty($property.required)}<span class="label label-danger" style="margin-right: .5em;">{translate text="Required" isAdminFacing=true}</span>{/if}
					{if !empty($property.returnValueForUnchecked) && $property.returnValueForUnchecked}
						<input type='hidden' name='{$propName}' id='{$propName}Unchecked' value='{if !empty($propValue)}{$propValue}{else}0{/if}'>
					{/if}
				</label>
				{include file="DataObjectUtil/fieldLockingInfo.tpl"}
				{if !empty($property.description)}<a style="margin-right: .5em; margin-left: .25em" id="{$propName}Tooltip" class="text-info" role="tooltip" tabindex="0" data-toggle="tooltip" data-placement="right" data-title="{translate text=$property.description isAdminFacing=true inAttribute=true}"><i class="fas fa-question-circle"></i></a>{/if}
			</div>
		{if !empty($property.forcesReindex)}<span id="{$propName}HelpBlock" class="help-block" style="margin-top:0"><small class="text-warning"><i class="fas fa-exclamation-triangle"></i> {translate text="Updating this setting causes a nightly reindex" isAdminFacing=true}</small></span>{/if}
		{if !empty($property.affectsLiDA)}<span id="{$propName}HelpBlock" class="help-block" style="margin-top:0"><small class="text-info"><i class="fas fa-info-circle"></i> {translate text="Aspen LiDA also uses this setting" isAdminFacing=true}</small></span>{/if}
		{if !empty($property.note)}<span id="{$propName}HelpBlock" class="help-block" style="margin-top:0"><small><i class="fas fa-info-circle"></i> {$property.note}</small></span>{/if}
		{elseif $property.type == 'webBuilderColor'}
			<section style="display: flex; flex-flow: row wrap; margin-top: 2rem;">
				{foreach from=$property.colorOptions item=colorOption}
				<div style="flex: 1; padding: 0.5rem; height: 100px">
					<input type="radio" id="{$propName}_{$colorOption}" name="{$propName}" value="{$colorOption}" {if ($propValue == $colorOption)}checked{/if}>
					<label for="{$propName}_{$colorOption}" style="background-color: {if $colorOption == 'primary'}{$primaryBackgroundColor}{elseif $colorOption == 'secondary'}{$secondaryBackgroundColor}{elseif $colorOption == 'tertiary'}{$tertiaryBackgroundColor}{else}inherit{/if}">
						<span style="font-size: 18px; font-weight: bold; color: inherit; text-transform: capitalize">{$colorOption}</span>
					</label>
				</div>
				{/foreach}
			</section>
		{elseif $property.type == 'oneToMany'}
			{if !empty($property.forcesReindex)}<span id="{$propName}HelpBlock" class="help-block"><small class="text-warning"><i class="fas fa-exclamation-triangle"></i> {translate text="Updating these settings causes a nightly reindex" isAdminFacing=true}</small></span>{/if}
			{if !empty($property.affectsLiDA)}<span id="{$propName}HelpBlock" class="help-block"><small class="text-info"><i class="fas fa-info-circle"></i> {translate text="Aspen LiDA also uses these settings" isAdminFacing=true}</small></span>{/if}
			{if !empty($property.note)}<span id="{$propName}HelpBlock" class="help-block"><small><i class="fas fa-info-circle"></i> {$property.note}</small></span>{/if}
			{include file="DataObjectUtil/oneToMany.tpl"}
		{elseif $property.type == 'portalRow'}
			{include file="DataObjectUtil/portalRows.tpl"}
		{elseif $property.type == 'translatableTextBlock'}
			<ul class="nav nav-tabs" role="tablist" id="{$propName}_language_tab">
				{if !empty($property.defaultTextFile)}
					<li role="presentation" class="active"><a href="#{$propName}_default_tab" aria-controls="{$propName}_default_tab" role="tab" data-toggle="tab">{translate text="Default" isAdminFacing=true}</a></li>
				{/if}
				{foreach from=$validLanguages key=languageCode item=language}
					{if $languageCode != 'ubb' && $languageCode != 'pig'}
						<li role="presentation" {if empty($property.defaultTextFile) && $userLang->code==$languageCode}class="active"{/if}><a href="#{$propName}_{$languageCode}_tab" aria-controls="{$propName}_{$languageCode}_tab" role="tab" data-toggle="tab">{$language->displayName|escape}</a></li>
					{/if}
				{/foreach}
			</ul>
			<div class="tab-content" id="{$propName}_languages">
				{if !empty($property.defaultTextFile)}
					{assign var='localPropName' value="`$propName`_default"}
					{assign var='localPropValue' value=$object->getTextBlockTranslation($property.property,'default')}
					{if empty($property.readOnly)}
						{assign var='localReadOnly' value=false}
					{else}
						{assign var='localReadOnly' value=$property.readOnly}
					{/if}
					<div role="tabpanel" class="tab-pane active" id="{$propName}_default_tab">
						{append var='property' value=true index='readOnly'}
						{include file="DataObjectUtil/textarea.tpl" propName=$localPropName propValue=$localPropValue}
						{append var='property' value=$localReadOnly index='readOnly'}
					</div>
				{/if}
				{foreach from=$validLanguages key=languageCode item=language}
					{if $languageCode != 'ubb' && $languageCode != 'pig'}
						{assign var='localPropName' value="`$propName`_`$languageCode`"}
						{assign var='localPropValue' value=$object->getTextBlockTranslation($property.property,$languageCode,false)}
						<div role="tabpanel" class="tab-pane {if empty($property.defaultTextFile) && $userLang->code==$languageCode}active{/if}" id="{$propName}_{$languageCode}_tab">
							<div class="form-group">
								{include file="DataObjectUtil/textarea.tpl" propName=$localPropName propValue=$localPropValue}
							</div>
							<div class="form-group">
								<div class="btn-group btn-group-sm">
									{if !empty($property.defaultTextFile)}
										<a class="btn btn-sm btn-default" onclick="tinyMCE.get('{$localPropName}').setContent(tinyMCE.get('{$propName}_default').getContent());return false;">{translate text="Copy From Default" isAdminFacing=true}</a>
									{/if}
									<a class="btn btn-sm btn-danger" onclick="tinyMCE.get('{$localPropName}').setContent('');return false;">{translate text="Clear" isAdminFacing=true}</a>
								</div>
							</div>
						</div>
					{/if}
				{/foreach}
			</div>
		{elseif $property.type == 'alert'}
			<div class="alert {if !empty($property.alertType)}{$property.alertType}{/if}">
				{$propValue}
			</div>
		{/if}

	</div>
{elseif $property.type == 'hidden'}
	<input type='hidden' id='{$propName}' name='{$propName}' value='{$propValue}'>
{/if}
{/strip}
