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
		//menubar: '',
		toolbar: 'image',
    		image_advtab: true,
		//
		images_upload_url: '/WebBuilder/AJAX?method=uploadImageTinyMCE',
		convert_urls: false,
		theme: 'modern',
		valid_elements : "@[id|class|style|title|dir<ltr?rtl|lang|xml::lang],"
    + "a[rel|rev|charset|hreflang|tabindex|name|href|target|title|class],strong/b,em/i,strike,u,"
    + "#p[style],-ol[type|compact],-ul[type|compact],-li,br,img[longdesc|usemap|"
    + "src|border|alt=|title|hspace|vspace|width|height|align],-sub,-sup,"
    + "-blockquote,-table[border=0|cellspacing|cellpadding|width|frame|rules|"
    + "height|align|summary|bgcolor|background|bordercolor],-tr[rowspan|width|"
    + "height|align|valign|bgcolor|background|bordercolor],tbody,thead,tfoot,"
    + "#td[colspan|rowspan|width|height|align|valign|bgcolor|background|bordercolor"
    + "|scope],#th[colspan|rowspan|width|height|align|valign|scope],caption,-div,"
    + "-span,-code,-pre,address,-h1,-h2,-h3,-h4,-h5,-h6,hr[size|noshade],-font[face"
    + "|size|color],embed[type|width|height|src|*],map[name],"
    + "button,col[align|char|charoff|span|valign|width],colgroup[align|char|charoff|span|"
    + "valign|width],fieldset,label[for],legend,noscript,"
    + "q[cite],small,textarea[cols|rows|disabled|name|readonly]",
		extended_valid_elements : [
			'*[*]',
		]
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
