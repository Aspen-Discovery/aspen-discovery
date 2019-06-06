<textarea name='{$propName}' id='{$propName}' {if !empty($property.rows)}rows='{$property.rows}'{/if} {if !empty($property.cols)}cols='{$property.cols}'{/if} title='{$property.description}' class='form-control {if !empty($property.required)}required{/if}' {if !empty($property.readOnly)}readonly{/if}>{$propValue|escape}</textarea>
{if $property.type == 'html'}
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
{/if}
