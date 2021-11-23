<h1>{translate text='Payment Completed' isPublicFacing=true}</h1>
{if !empty($error)}
    <div class="row">
        <div class="col-xs-12">
            <div class="alert alert-danger">{translate text=$error isPublicFacing=true}</div>
        </div>
    </div>
{/if}
{if !empty($message)}
    <div class="row">
        <div class="col-xs-12">
            <div class="alert alert-success">{translate text=$message isPublicFacing=true}</div>
        </div>
    </div>
{/if}