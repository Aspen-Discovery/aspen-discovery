{strip}
<textarea name='{$propName}' id='{$propName}' {if !empty($property.rows)}rows='{$property.rows}'{/if} {if !empty($property.cols)}cols='{$property.cols}'{/if} {if !empty($property.description)}title='{$property.description}'{/if} class='form-control {if !empty($property.required) && $objectAction != 'edit'}required{/if}' {if !empty($property.readOnly)}readonly{/if} {if !empty($property.maxLength)}maxlength="{$property.maxLength}" {/if}>
{$propValue|escape}
</textarea>
{/strip}
{*{if !empty($property.readOnly)}*}
	{if $property.type == 'html' || $property.type == 'translatableTextBlock' || ($property.type == 'markdown' && $useHtmlEditorRatherThanMarkdown)}
		<script>
		{literal}
		tinymce.init({
			selector: '#{/literal}{$propName}{literal}',
			{/literal}
			{if !empty($property.readOnly)}
				plugins: '',
				toolbar1: '',
				toolbar2: '',
				toolbar3: '',
				toolbar: 'image',
			{else}
				plugins: 'anchor autolink autoresize autosave code codesample colorpicker contextmenu directionality fullscreen help hr image imagetools insertdatetime link lists media paste preview print save searchreplace table textcolor textpattern toc visualblocks visualchars wordcount tinymceEmoji',
				toolbar1: 'code | cut copy paste pastetext | undo redo searchreplace | image table hr codesample insertdatetime | link anchor | tinymceEmoji',
				toolbar2: 'bold italic underline strikethrough | formatselect fontselect fontsizeselect forecolor backcolor',
				toolbar3: 'numlist bullist toc | alignleft aligncenter alignright | preview visualblocks fullscreen help',
				toolbar: 'image',
			{/if}
			{literal}
			menubar:'',
			image_advtab: true,
			images_upload_url: '/WebBuilder/AJAX?method=uploadImageTinyMCE',
			convert_urls: false,
			theme: 'modern',
			valid_elements : '*[*]',
			extended_valid_elements : [
				'*[*]',
				'img[class=img-responsive|*]'
			],
			emoji_show_groups: false,
			emoji_show_subgroups: false,
			browser_spellcheck: true,
			{/literal}
			readonly:{if !empty($property.readOnly)}1,{else}0,{/if}
			{literal}
		});
		{/literal}
{*		{if !empty($property.readOnly)}*}
{*			tinyMCE.get('#{$propName}').getBody().setAttribute('contenteditable', false);*}
{*		{/if}*}
		</script>
	{elseif $property.type == 'markdown'}
		<script type="text/javascript">
			$(document).ready(function(){ldelim}
				var characterLimit{$propName} = $("#{$propName}").prop('maxlength');
				if (characterLimit{$propName} === undefined) {
					characterLimit{$propName} = 0;
				}
				var simplemde{$propName} = new SimpleMDE({ldelim}
					element: $("#{$propName}")[0],
					{if empty($property.readOnly)}
						toolbar: ["heading-1", "heading-2", "heading-3", "heading-smaller", "heading-bigger", "|",
							"bold", "italic", "strikethrough", "|",
							"quote", "unordered-list", "ordered-list", "|",
							"link", "image", {ldelim}name:"uploadImage", action:function (){ldelim} return AspenDiscovery.WebBuilder.getUploadImageForm('{$propName}'){rdelim},className: "fa fa-upload",title: "Upload Image"{rdelim}, "|",
							"preview", "guide"],
						status: [ {ldelim}
							className: "chars",
							defaultValue: function(el) {ldelim}
								if (characterLimit{$propName} > 0) {ldelim}
									el.innerHTML = "0 / " + characterLimit{$propName};
								{rdelim}
							{rdelim},
							onUpdate: function(el) {ldelim}
								if (characterLimit{$propName} > 0) {ldelim}
									el.innerHTML = simplemde{$propName}.value().length + " / "+characterLimit{$propName};
									AspenDiscovery.limitMarkdownField(simplemde{$propName}, characterLimit{$propName});
								{rdelim}
							{rdelim}
						{rdelim}],
					{else}
						toolbar: false,
						status: false,
					{/if}
				{rdelim});
				AspenDiscovery.WebBuilder.editors['{$propName}'] = simplemde{$propName};
				if (characterLimit{$propName} > 0) {
					AspenDiscovery.limitMarkdownField(simplemde{$propName}, characterLimit{$propName});
				}
				{if !empty($property.readOnly)}
					AspenDiscovery.WebBuilder.editors['{$propName}'].codemirror.options.readOnly = true;
				{/if}
			{rdelim});
		</script>
	{/if}
{*{/if}*}