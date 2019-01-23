{foreach from=$commentList item=comment}
  <div class='comment'>
  	<div class="commentHeader">
    <div class='commentDate'>{$comment->created|date_format}
	    {if $loggedIn && ($comment->user_id == $activeUserId || array_key_exists('opacAdmin', $userRoles))}
	    <span onclick='deleteComment("{$id|escape:"url"}", {$comment->id}, {literal}{{/literal}save_error: "{translate text='comment_error_save'}", load_error: "{translate text='comment_error_load'}", save_title: "{translate text='Save Comment'}"{literal}}{/literal});' class="deleteComment"><span class="silk delete">&nbsp;</span>{translate text='Delete'}</span>
	    {/if}
    </div>
    <div class="posted"><strong>{translate text='Review by'} {if strlen($comment->displayName) > 0}{$comment->displayName}{else}{$comment->fullname}{/if}</strong></div>
    </div>
    {$comment->comment|escape:"html"}
  </div>
{/foreach}