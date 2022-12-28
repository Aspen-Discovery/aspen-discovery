<div align="left">
    {if !empty($message)}<div class="error">{translate text=$message isPublicFacing=true}</div>{/if}

    {if empty($message)}<p>{translate text="You currently have these categories hidden" isPublicFacing=true}</p>
        <form id="updateBrowseCategories" class="form">
            {foreach from=$hiddenBrowseCategories key=k item=category}
            <div class="checkbox">
               <label><input type="checkbox" name="selected[{$category.id}]" class="categorySelect" id="selected{$category.id}">{$category.name}</label>
            </div>
            {/foreach}
                <input type="hidden" value="{$patronId}" name="patronId"/>
        </form>
    {/if}
</div>

<script type="text/javascript">
    {literal}
    $("#updateBrowseCategories").validate({
        submitHandler: function(){
            AspenDiscovery.Account.showBrowseCategory();
        }
    });
    {/literal}
</script>