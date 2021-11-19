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
	<div {if $addFormGroupToProperty !== false}class="form-group"{/if} id="propertyRow{$propName}">
		{* Output the label *}
		{if $property.type == 'enum'}
			{if $property.renderAsHeading == true}
				{if $property.required && $objectAction != 'edit'}
					<div style="margin-bottom: .5em">
						<h2 style="display: inline; vertical-align: top; margin-right: .25em">{translate text=$property.label isAdminFacing=true}</h2>
						<span class="label label-danger" style="margin-right: .5em">{translate text="Required" isAdminFacing=true}</span>
					</div>
				{else}
					<h2>{translate text=$property.label isAdminFacing=true}</h2>
				{/if}
			{else}
				<label for='{$propName}Select'{if $property.description} title="{translate text=$property.description isAdminFacing=true inAttribute=true}"{/if} style="margin-right: .5em">
					{translate text=$property.label isAdminFacing=true}
				</label>
				{if $property.required && $objectAction != 'edit'}
					<span class="label label-danger" style="margin-right: .5em">{translate text="Required" isAdminFacing=true}</span>
				{/if}
			{/if}
		{elseif $property.type == 'oneToMany' && !empty($property.helpLink)}
			<div class="row">
				<div class="col-xs-11">
				{if $property.renderAsHeading == true}
					{if $property.required && $objectAction != 'edit'}
						<div style="margin-bottom: .5em">
							<h2 style="display: inline; vertical-align: top; margin-right: .25em">{translate text=$property.label isAdminFacing=true}</h2>
							<span class="label label-danger" style="margin-right: .5em">{translate text="Required" isAdminFacing=true}</span>
						</div>
					{else}
						<h2>{translate text=$property.label isAdminFacing=true}</h2>
					{/if}
				{else}
					<label for='{$propName}'{if $property.description} title="{translate text=$property.description isAdminFacing=true inAttribute=true}"{/if} style="margin-right: .5em">
						{translate text=$property.label isAdminFacing=true}
					</label>
					{if $property.required && $objectAction != 'edit'}
						<span class="label label-danger" style="margin-right: .5em">{translate text="Required" isAdminFacing=true}</span>
					{/if}
				{/if}
				</div>
				<div class="col-xs-1">
					<a href="{$property.helpLink}" target="_blank"><img src="/interface/themes/responsive/images/help.png" alt="Help"></a>
				</div>
			</div>
		{elseif $property.type != 'section' && $property.type != 'checkbox' && $property.type != 'hidden'}
			{if $property.renderAsHeading == true}
				{if $property.required && $objectAction != 'edit'}
					<div style="margin-bottom: .5em">
						<h2 style="display: inline; vertical-align: top; margin-right: .25em">{translate text=$property.label isAdminFacing=true}</h2>
						<span class="label label-danger" style="margin-right: .5em">{translate text="Required" isAdminFacing=true}</span>
					</div>
				{else}
					<h2>{translate text=$property.label isAdminFacing=true}</h2>
				{/if}
			{else}
				<label for='{$propName}'{if $property.description} title="{$property.description}"{/if} style="margin-right: .5em">{translate text=$property.label isAdminFacing=true}</label>
				{if $property.required && $objectAction != 'edit'}
					<span class="label label-danger" style="margin-right: .5em">{translate text="Required" isAdminFacing=true}</span>
				{/if}
			{/if}
		{/if}
		{if !empty($property.showDescription)}
			<div class='propertyDescription'><em>{$property.description}</em></div>
		{/if}
		{* Output the editing control*}
		{if $property.type == 'section'}
			{if !empty($property.renderAsHeading) && $property.renderAsHeading == true}
				<div class="row">
					<div class="col-xs-12">
						{if !empty($property.label)}
							<h2>{translate text=$property.label isAdminFacing=true}</h2>
						{/if}

						{foreach from=$property.properties item=property}
							{include file="DataObjectUtil/property.tpl"}
						{/foreach}
					</div>
				</div>
			{else}
				<div class="panel-group" id="accordion_{$property.label|escapeCSS}">
					<div class="panel panel-default {if !empty($property.expandByDefault)}active{/if}">
						<div class="panel-heading row">
							<div class="panel-title col-xs-11">
								<a data-toggle="collapse" data-parent="#accordion_{$property.label|escapeCSS}" href="#accordion_body_{$property.label|escapeCSS}">
									{translate text=$property.label isAdminFacing=true}
								</a>
							</div>
							{if $property.helpLink}
								<div class="col-xs-1">
									<a href="{$property.helpLink}" target="_blank"><img src="/interface/themes/responsive/images/help.png" alt="Help"></a>
								</div>
							{/if}
						</div>

						<div id="accordion_body_{$property.label|escapeCSS}" class="panel-collapse {if !empty($property.expandByDefault)}in{else}collapse{/if}">
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
			{/if}
        {elseif $property.type == 'foreignKey' && !empty($property.editLink)}
			<div class="row">
				<div class="col-sm-12">
					<a class="btn btn-default btn-sm" href="{$property.editLink|replace:'propertyValue':$propValue}">Edit {$property.label}</a>
				</div>
			</div>
        {elseif $property.type == 'text' || $property.type == 'regularExpression' || $property.type == 'folder'}
			<input type='text' name='{$propName}' id='{$propName}' value='{$propValue|escape}' {if !empty($property.accessibleLabel)}aria-label="{$property.accessibleLabel}"{/if} {if $property.maxLength}maxlength='{$property.maxLength}'{/if} {if !empty($property.size)}size='{$property.size}'{/if} class='form-control {if $property.required && $objectAction != 'edit'}required{/if}' {if !empty($property.readOnly)}readonly{/if}  {if $property.forcesReindex}aria-describedby="{$propName}HelpBlock"{/if}>
			{if $property.forcesReindex}<span id="{$propName}HelpBlock" class="help-block" style="margin-top:0"><small class="text-warning"><i class="fas fa-exclamation-circle"></i> {translate text="Updating this setting causes a nightly reindex" isAdminFacing=true}</small></span>{/if}
			{if $property.affectsLiDA}<span id="{$propName}HelpBlock" class="help-block" style="margin-top:0"><small class="text-info"><i class="fas fa-info-circle"></i> {translate text="Aspen LiDA also uses this setting" isAdminFacing=true}</small></span>{/if}
			{if $property.note}<span id="{$propName}HelpBlock" class="help-block" style="margin-top:0"><small><i class="fas fa-info-circle"></i> {$property.note}</small></span>{/if}
		{elseif $property.type == 'integer'}
			<input type='number' name='{$propName}' id='{$propName}' value='{$propValue|escape}' {if !empty($property.accessibleLabel)}aria-label="{$property.accessibleLabel}"{/if} {if $property.max}max="{$property.max}"{/if} {if $property.min}min="{$property.min}"{/if} {if $property.maxLength}maxlength='{$property.maxLength}'{/if} {if !empty($property.size)}size='{$property.size}'{/if} class='form-control {if $property.required && $objectAction != 'edit'}required{/if}' {if !empty($property.readOnly)}readonly{/if}>
			{if $property.forcesReindex}<span id="{$propName}HelpBlock" class="help-block"><small class="text-warning"><i class="fas fa-exclamation-circle"></i> {translate text="Updating this setting causes a nightly reindex" isAdminFacing=true}</small></span>{/if}
			{if $property.affectsLiDA}<span id="{$propName}HelpBlock" class="help-block"><small class="text-info"><i class="fas fa-info-circle"></i> {translate text="Aspen LiDA also uses this setting" isAdminFacing=true}</small></span>{/if}
			{if $property.note}<span id="{$propName}HelpBlock" class="help-block"><small><i class="fas fa-info-circle"></i> {$property.note}</small></span>{/if}
		{elseif $property.type == 'timestamp'}
			<div class="row">
				<div class="col-sm-4">
					<input name='{$propName}' id='{$propName}' value='{if !empty($propValue)}{$propValue|date_format:"%Y-%m-%d %H:%M"}{/if}' {if !empty($property.accessibleLabel)}aria-label="{$property.accessibleLabel}"{/if} {if $property.max}max="{$property.max}"{/if} {if $property.min}min="{$property.min}"{/if} {if $property.maxLength}maxlength='{$property.maxLength}'{/if} {if !empty($property.size)}size='{$property.size}'{/if} class='form-control {if $property.required && $objectAction != 'edit'}required{/if}' {if !empty($property.readOnly)}readonly{/if}>
				</div>
				<script type="text/javascript">
					$(document).ready(function(){ldelim}
						rome({$propName});
					{rdelim});
				</script>
			</div>
			{if $property.forcesReindex}<span id="{$propName}HelpBlock" class="help-block" style="margin-top:0"><small class="text-warning"><i class="fas fa-exclamation-circle"></i> {translate text="Updating this setting causes a nightly reindex" isAdminFacing=true}</small></span>{/if}
			{if $property.affectsLiDA}<span id="{$propName}HelpBlock" class="help-block" style="margin-top:0"><small class="text-info"><i class="fas fa-info-circle"></i> {translate text="Aspen LiDA also uses this setting" isAdminFacing=true}</small></span>{/if}
			{if $property.note}<span id="{$propName}HelpBlock" class="help-block" style="margin-top:0"><small><i class="fas fa-info-circle"></i> {$property.note}</small></span>{/if}
		{elseif $property.type == 'url'}
			<input type='text' name='{$propName}' id='{$propName}' value='{$propValue|escape}' {if !empty($property.accessibleLabel)}aria-label="{$property.accessibleLabel}"{/if} {if $property.maxLength}maxlength='{$property.maxLength}'{/if} {if !empty($property.size)}size='{$property.size}'{/if} class='form-control url {if $property.required && $objectAction != 'edit'}required{/if}' {if !empty($property.readOnly)}readonly{/if}>
			{if $property.forcesReindex}<span id="{$propName}HelpBlock" class="help-block" style="margin-top:0"><small class="text-warning"><i class="fas fa-exclamation-circlee"></i> {translate text="Updating this setting causes a nightly reindex" isAdminFacing=true}</small></span>{/if}
			{if $property.affectsLiDA}<span id="{$propName}HelpBlock" class="help-block" style="margin-top:0"><small class="text-info"><i class="fas fa-info-circle"></i> {translate text="Aspen LiDA also uses this setting" isAdminFacing=true}</small></span>{/if}
			{if $property.note}<span id="{$propName}HelpBlock" class="help-block" style="margin-top:0"><small><i class="fas fa-info-circle"></i> {$property.note}</small></span>{/if}
		{elseif $property.type == 'email'}
			<input type='text' name='{$propName}' id='{$propName}' value='{$propValue|escape}' {if !empty($property.accessibleLabel)}aria-label="{$property.accessibleLabel}"{/if} {if $property.maxLength}maxlength='{$property.maxLength}'{/if} {if !empty($property.size)}size='{$property.size}'{/if} class='form-control email {if $property.required && $objectAction != 'edit'}required{/if}' {if !empty($property.readOnly)}readonly{/if}>
			{if $property.forcesReindex}<span id="{$propName}HelpBlock" class="help-block" style="margin-top:0"><small class="text-warning"><i class="fas fa-exclamation-circle"></i> {translate text="Updating this setting causes a nightly reindex" isAdminFacing=true}</small></span>{/if}
			{if $property.affectsLiDA}<span id="{$propName}HelpBlock" class="help-block" style="margin-top:0"><small class="text-info"><i class="fas fa-info-circle"></i> {$property.affectsLiDA}</small></span>{/if}
			{if $property.note}<span id="{$propName}HelpBlock" class="help-block" style="margin-top:0"><small><i class="fas fa-info-circle"></i> {translate text="Aspen LiDA also uses this setting" isAdminFacing=true}</small></span>{/if}
		{elseif $property.type == 'color'}
			<div class="row">
				<div class="col-tn-3">
					<input type='color' name='{$propName}' id='{$propName}' value='{$propValue|escape}'  aria-label='{$property.label} color picker' class='form-control{if $property.required && $objectAction != 'edit'}required{/if}' size="7" maxlength="7" onchange="$('#{$propName}Hex').val(this.value);$('#{$propName}-default').prop('checked',false);{if !empty($property.checkContrastWith)}AspenDiscovery.Admin.checkContrast('{$propName}', '{$property.checkContrastWith}');{/if}" {if !empty($property.readOnly)}readonly{/if}>
				</div>
				<div class="col-tn-3">
					<input type='text' id='{$propName}Hex' value='{$propValue|escape}' aria-label='{$property.label} hex code' class='form-control' size="7" maxlength="7" onchange="$('#{$propName}').val(this.value);$('#{$propName}-default').prop('checked',false);{if !empty($property.checkContrastWith)}AspenDiscovery.Admin.checkContrast('{$propName}', '{$property.checkContrastWith}');{/if}" pattern="^#([a-fA-F0-9]{ldelim}6{rdelim})$" {if !empty($property.readOnly)}readonly{/if}>
				</div>
				<div class="col-tn-3">
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
				<div class="col-tn-3">
					{if !empty($property.checkContrastWith)}
						&nbsp;{translate text='Contrast Ratio' isAdminFacing=true}&nbsp;<span id="contrast_{$propName}" class="contrast_warning"></span>
						<script type="text/javascript">
							$(document).ready(function(){ldelim}
								AspenDiscovery.Admin.checkContrast('{$propName}', '{$property.checkContrastWith}'{if !empty($property.checkContrastOneWay) && $property.checkContrastOneWay==true},true{/if});
							{rdelim});
						</script>
					{/if}
				</div>
			</div>
			{assign var=fetchDefaultColor value='default|cat:$propName'}
		{literal}
			<script type="text/javascript">
				var setDefaultColor = $('#{/literal}{$propName}{literal}-default');
				$('#{/literal}{$propName}{literal}-default').on("click", function() {

					if($(this).is(":checked")) {
						$(this).attr("checked", true);
						AspenDiscovery.Admin.getDefaultColor('{/literal}{$propName}{literal}','{/literal}{$fetchDefaultColor}{literal}');
						{/literal}
						{if !empty($property.checkContrastWith)}AspenDiscovery.Admin.checkContrast('{$propName}', '{$property.checkContrastWith}');{/if}
						{literal}
					} else {
						$(this).attr("checked", false);
						document.getElementById('{/literal}{$propName}{literal}').value = '{/literal}{$propValue}{literal}';
						document.getElementById('{/literal}{$propName}{literal}Hex').value = '{/literal}{$propValue}{literal}';
						{/literal}
						{if !empty($property.checkContrastWith)}AspenDiscovery.Admin.checkContrast('{$propName}', '{$property.checkContrastWith}');{/if}
						{literal}
					}
				});
			</script>
		{/literal}
		{elseif $property.type == 'font'}
			<div class="row">
				<div class="col-sm-4">
					<select name='{$propName}' id='{$propName}' class='form-control font {if $property.required && $objectAction != 'edit'}required{/if}' {if !empty($property.readOnly)}readonly{/if} onchange="$('#{$propName}-default').prop('checked',false);AspenDiscovery.Admin.loadGoogleFontPreview('{$propName}')">
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
					<div id="{$propName}-sample-text" style="font-family: {$propValue},arial; font-size: {if $property.previewFontSize}{$property.previewFontSize}{else}12px{/if}">
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
					<div id="{$propName}-sample-text" style="font-family: {$propValue},arial; font-size: {if $property.previewFontSize}{$property.previewFontSize}{else}12px{/if}">
						English, Español, 中文(简体), עברית
					</div>
				</div>
			</div>
		{elseif $property.type == 'multiemail'}
			<input type='text' name='{$propName}' id='{$propName}' value='{$propValue|escape}' {if !empty($property.accessibleLabel)}aria-label="{$property.accessibleLabel}"{/if} {if $property.maxLength}maxlength='{$property.maxLength}'{/if} {if !empty($property.size)}size='{$property.size}'{/if} class='form-control multiemail {if $property.required && $objectAction != 'edit'}required{/if}' {if !empty($property.readOnly)}readonly{/if}>
		{elseif $property.type == 'date'}
			{*<input type='{$property.type}' name='{$propName}' id='{$propName}' value='{$propValue}' {if $property.maxLength}maxLength='10'{/if}	class='form-control {if $property.required && $objectAction != 'edit'}required{/if} date'>*}
			{* disable html5 features until consistly implemented *
			{*<input type='text' name='{$propName}' id='{$propName}' value='{$propValue}' {if $property.maxLength}maxLength='10'{/if}	class='form-control {if $property.required && $objectAction != 'edit'}required{/if} date'>*}
			{*<input type='text' name='{$propName}' id='{$propName}' value='{$propValue}' {if !empty($property.accessibleLabel)}aria-label="{$property.accessibleLabel}"{/if} {if $property.maxLength}maxLength='10'{/if}	class='form-control {if $property.required && $objectAction != 'edit'}required{/if} dateAspen' {if !empty($property.readOnly)}readonly{/if}>*}
			<input type="date" name='{$propName}' id='{$propName}' value='{$propValue|date_format:"%Y-%m-%d"}'	class='form-control' {if $property.required && $objectAction != 'edit'}required{/if}>
		{elseif $property.type == 'partialDate'}
			{include file="DataObjectUtil/partialDate.tpl"}

		{elseif $property.type == 'textarea' || $property.type == 'html' || $property.type == 'markdown' || $property.type == 'javascript' || $property.type == 'crSeparated'}
			{include file="DataObjectUtil/textarea.tpl"}
			{if $property.forcesReindex}<span id="{$propName}HelpBlock" class="help-block" style="margin-top:0"><small class="text-warning"><i class="fas fa-exclamation-circle"></i> {translate text="Updating this setting causes a nightly reindex" isAdminFacing=true}</small></span>{/if}
			{if $property.affectsLiDA}<span id="{$propName}HelpBlock" class="help-block" style="margin-top:0"><small class="text-info"><i class="fas fa-info-circle"></i> {translate text="Aspen LiDA also uses this setting" isAdminFacing=true}</small></span>{/if}
			{if $property.note}<span id="{$propName}HelpBlock" class="help-block" style="margin-top:0"><small><i class="fas fa-info-circle"></i> {$property.note}</small></span>{/if}

		{elseif $property.type == 'password' || $property.type == 'storedPassword'}
			{include file="DataObjectUtil/password.tpl"}

		{elseif $property.type == 'pin'}
			<input type='password' name='{$propName}' id='{$propName}' value='{$propValue|escape}' {if $property.maxLength}maxlength='{$property.maxLength}'{/if} {if !empty($property.size)}size='{$property.size}'{/if} class='form-control digits {if $property.required && $objectAction != 'edit'}required{/if}' {if !empty($property.readOnly)}readonly{/if}>


		{elseif $property.type == 'currency'}
			{include file="DataObjectUtil/currency.tpl"}

		{elseif $property.type == 'label'}
			<div id='{$propName}'>{$propValue}</div>
			{if $property.forcesReindex}<span id="{$propName}HelpBlock" class="help-block" style="margin-top:0"><small class="text-warning"><i class="fas fa-exclamation-circle"></i> {translate text="Updating this setting causes a nightly reindex" isAdminFacing=true}</small></span>{/if}
			{if $property.affectsLiDA}<span id="{$propName}HelpBlock" class="help-block" style="margin-top:0"><small class="text-info"><i class="fas fa-info-circle"></i> {translate text="Aspen LiDA also uses this setting" isAdminFacing=true}</small></span>{/if}
			{if $property.note}<span id="{$propName}HelpBlock" class="help-block" style="margin-top:0"><small><i class="fas fa-info-circle"></i> {$property.note}</small></span>{/if}

		{elseif $property.type == 'enum'}
			{include file="DataObjectUtil/enum.tpl"}
			{if $property.forcesReindex}<span id="{$propName}HelpBlock" class="help-block" style="margin-top:0"><small class="text-warning"><i class="fas fa-exclamation-circle"></i> {translate text="Updating this setting causes a nightly reindex" isAdminFacing=true}</small></span>{/if}
			{if $property.affectsLiDA}<span id="{$propName}HelpBlock" class="help-block" style="margin-top:0"><small class="text-info"><i class="fas fa-info-circle"></i> {translate text="Aspen LiDA also uses this setting" isAdminFacing=true}</small></span>{/if}
			{if $property.note}<span id="{$propName}HelpBlock" class="help-block" style="margin-top:0"><small><i class="fas fa-info-circle"></i> {$property.note}</small></span>{/if}

		{elseif $property.type == 'multiSelect'}
			{include file="DataObjectUtil/multiSelect.tpl"}
			{if $property.forcesReindex}<span id="{$propName}HelpBlock" class="help-block" style="margin-top:0"><small class="text-warning"><i class="fas fa-exclamation-circle"></i> {translate text="Updating this setting causes a nightly reindex" isAdminFacing=true}</small></span>{/if}
			{if $property.affectsLiDA}<span id="{$propName}HelpBlock" class="help-block" style="margin-top:0"><small class="text-info"><i class="fas fa-info-circle"></i> {translate text="Aspen LiDA also uses this setting" isAdminFacing=true}</small></span>{/if}
			{if $property.note}<span id="{$propName}HelpBlock" class="help-block" style="margin-top:0"><small><i class="fas fa-info-circle"></i> {$property.note}</small></span>{/if}

		{elseif $property.type == 'image' || $property.type == 'file'}
			{if !empty($propValue) && $property.type == 'image'}
				{if $property.thumbWidth}
					<img src='/files/thumbnail/{$propValue}' style="display: block" alt="Selected Image for {$property.label}">
				{else}
					{if $property.displayUrl}
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
							{translate text="Select an image" isAdminFacing=true}&hellip; <input type="file" style="display: none;" name="{$propName}" id="{$propName}" {if $property.required && $objectAction != 'edit'}required="required"{/if}>
						{else}
							{translate text="Select a file" isAdminFacing=true}&hellip; <input type="file" style="display: none;" name="{$propName}" id="{$propName}" {if $property.required && $objectAction != 'edit'}required="required"{/if}>
						{/if}
					</span>
				</label>
				<input type="text" class="form-control" id="importFile-label-{$propName}" readonly value="{$propValue}">
				<input type='hidden' name='{$propName}_existing' id='{$propName}_existing' value='{$propValue|escape}'>
			</div>
			{if !empty($propValue)}
				<div class="checkbox" style="margin-top: 0">
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
		{elseif $property.type == 'checkbox'}
			<div class="checkbox" {if $property.forcesReindex || $property.affectsLiDA || $property.note}style="margin-bottom: 0"{/if}>
				<label for='{$propName}'{if $property.description} title="{$property.description}"{/if}>
					<input type='checkbox' name='{$propName}' id='{$propName}' {if ($propValue == 1)}checked='checked'{/if} {if $property.required && $objectAction != 'edit'}<span class="label label-danger" style="margin-right: .5em;">{translate text="Required" isAdminFacing=true}</span>{/if} {if !empty($property.readOnly)}readonly{/if}> {translate text=$property.label isAdminFacing=true}
				</label>

			</div>
		{if $property.forcesReindex}<span id="{$propName}HelpBlock" class="help-block" style="margin-top:0"><small class="text-warning"><i class="fas fa-exclamation-circle"></i> {translate text="Updating this setting causes a nightly reindex" isAdminFacing=true}</small></span>{/if}
		{if $property.affectsLiDA}<span id="{$propName}HelpBlock" class="help-block" style="margin-top:0"><small class="text-info"><i class="fas fa-info-circle"></i> {translate text="Aspen LiDA also uses this setting" isAdminFacing=true}</small></span>{/if}
		{if $property.note}<span id="{$propName}HelpBlock" class="help-block" style="margin-top:0"><small><i class="fas fa-info-circle"></i> {$property.note}</small></span>{/if}
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
			{if $property.forcesReindex}<span id="{$propName}HelpBlock" class="help-block"><small class="text-warning"><i class="fas fa-exclamation-circle"></i> {translate text="Updating these settings causes a nightly reindex" isAdminFacing=true}</small></span>{/if}
			{if $property.affectsLiDA}<span id="{$propName}HelpBlock" class="help-block"><small class="text-info"><i class="fas fa-info-circle"></i> {translate text="Aspen LiDA also uses these settings" isAdminFacing=true}</small></span>{/if}
			{if $property.note}<span id="{$propName}HelpBlock" class="help-block"><small><i class="fas fa-info-circle"></i> {$property.note}</small></span>{/if}
			{include file="DataObjectUtil/oneToMany.tpl"}
		{elseif $property.type == 'portalRow'}
			{include file="DataObjectUtil/portalRows.tpl"}
		{/if}

	</div>
{elseif $property.type == 'hidden'}
	<input type='hidden' name='{$propName}' value='{$propValue}'>
{/if}
{/strip}