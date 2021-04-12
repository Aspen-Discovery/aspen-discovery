<textarea name='{$propName}' id='{$propName}' {if !empty($property.rows)}rows='{$property.rows}'{/if} {if !empty($property.cols)}cols='{$property.cols}'{/if} title='{$property.description}' class='form-control {if !empty($property.required && $objectAction != 'edit')}required{/if}' {if !empty($property.readOnly)}readonly{/if}>{$propValue|escape}</textarea>
{if $property.type == 'html' || ($property.type == 'markdown' && $useHtmlEditorRatherThanMarkdown)}
	<script type="text/javascript">
	{literal}
	$(document).ready(function(){
		CKEDITOR.replace( '{/literal}{$propName}{literal}',
		{
		toolbar : [
			['Source','-','Save'],
			['Cut','Copy','Paste','PasteText','PasteFromWord','-','SpellChecker','Scayt'],
			['Undo','Redo','-','Find','Replace','-','SelectAll','RemoveFormat'],
			'/',
			['Bold','Italic','Underline','Strike','-','Subscript','Superscript'],
			['Styles','Font','FontSize'],
			['TextColor','BGColor'],
			['Link','Unlink','Anchor'],
			['Maximize', 'ShowBlocks','-','About']
		]
		});
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
