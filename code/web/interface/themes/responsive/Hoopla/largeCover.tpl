{strip}
    <form enctype="multipart/form-data" name="getLargeCover" id="getLargeCover" method="post" action="/Hoopla/{$id}/AJAX">
        <input type="hidden" name="id" value="{$id}"/>

        <div class="form-group">
            <div id="recordCover" class="text-center row">
                <img alt="{translate text='Book Cover' inAttribute=true}" class="img-thumbnail" src="/bookcover.php?id={$id}&size=large&type=hoopla">
            </div>

        </div>
    </form>
{/strip}