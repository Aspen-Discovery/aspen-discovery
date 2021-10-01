<textarea name='{$propName}' id='{$propName}' {if !empty($property.rows)}rows='{$property.rows}'{/if} {if !empty($property.cols)}cols='{$property.cols}'{/if} title='{$property.description}' class='form-control {if !empty($property.required && $objectAction != 'edit')}required{/if}' {if !empty($property.readOnly)}readonly{/if}>{$propValue|escape}</textarea>
{if $property.type == 'html' || ($property.type == 'markdown' && $useHtmlEditorRatherThanMarkdown)}
	<script>
	{literal}
	tinymce.init({
		selector: '#{/literal}{$propName}{literal}',
		plugins: 'anchor autolink autoresize autosave code codesample colorpicker contextmenu directionality fullscreen help hr image imagetools insertdatetime link lists media paste preview print save searchreplace spellchecker table textcolor textpattern toc visualblocks visualchars wordcount',
		toolbar1: 'code | cut copy paste pastetext spellchecker | undo redo searchreplace | image table hr codesample insertdatetime | link anchor ',
		toolbar2: 'bold italic underline strikethrough | formatselect fontselect fontsizeselect forecolor backcolor',
		toolbar3: 'numlist bullist toc | alignleft aligncenter alignright | preview visualblocks fullscreen help',
		menubar: '',
		images_upload_url: '/WebBuilder/AJAX?method=uploadImageTinyMCE',
		convert_urls: false,
		theme: 'modern',
		valid_elements : '*[*]',
		extended_valid_elements : '*[*]',
	});
	{/literal}
	</script>
{elseif $property.type == 'markdown'}
	<script type="text/javascript">
		$(document).ready(function(){ldelim}
			var simplemde{$propName} = new SimpleMDE({ldelim}
				element: $("#{$propName}")[0],
				toolbar: ["heading-1", "heading-2", "heading-3", "heading-smaller", "heading-bigger", "|",
					"bold", "italic", "strikethrough", "|",
					"quote", "unordered-list", "ordered-list", "|",
					"link", "image", {ldelim}name:"uploadImage", action:function (){ldelim} return AspenDiscovery.WebBuilder.getUploadImageForm('{$propName}'){rdelim},className: "fa fa-upload",title: "Upload Image"{rdelim}, "|",
					"preview", "guide"],
			{rdelim});
			AspenDiscovery.WebBuilder.editors['{$propName}'] = simplemde{$propName};
		{rdelim});
	</script>
{/if}
