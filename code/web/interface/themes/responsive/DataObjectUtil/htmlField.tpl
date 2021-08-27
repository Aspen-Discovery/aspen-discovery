<br/><textarea name='{$propName}' id='{$propName}' rows='{$property.rows}' cols='{$property.cols}' title='{trasnslate text=$property.description inAttribute=true isAdminFacing=true}' class='{if $property.required}required{/if}'>{$propValue|escape}</textarea>
<script type="text/javascript">
{literal}
CKEDITOR.replace( '{/literal}{$propName}{literal}',
    {
          toolbar : 'Basic', height:'{/literal}{$property.rows*20+30}{literal}px'
    });

//Update the instance so jquery validation works the first time. 
CKEDITOR.instances['{/literal}{$propName}{literal}'].on("instanceReady", function()
{
//set keyup event
this.document.on("keyup", update_ckEditor_{/literal}{$propName}{literal});

 //and paste event
this.document.on("paste", update_ckEditor_{/literal}{$propName}{literal});
});

function update_ckEditor_{/literal}{$propName}{literal}()
{

    CKEDITOR.tools.setTimeout( function()
    { 
        $("#{/literal}{$propName}{literal}").val(CKEDITOR.instances.{/literal}{$propName}{literal}.getData()); 
    }, 0);
}

{/literal}
</script>
