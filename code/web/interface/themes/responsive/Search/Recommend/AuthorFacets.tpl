{if $similarAuthors}
  <div class="authorbox">
	  <h5>{translate text='Authors matching' isPublicFacing=true}: {$lookfor|escape}</h5>
	  <div class="row">
	  	{foreach from=$similarAuthors.list item=author name=authorLoop}
				{if $smarty.foreach.authorLoop.iteration % 4 == 1}
			    {if $smarty.foreach.authorLoop.iteration != 1}
				    </div>
	          <div class="row">
				  {/if}
				{/if}
			  <div class="col-md-3">
				  <a href="{$author.url|escape}">{$author.value|escape}</a>
				</div>
		  {/foreach}
	  </div>
		{if count($similarAuthors.list) == 10}
			<div>
			  <a href="{$similarAuthors.lookfor|escape}"><strong>{translate text='see all' isPublicFacing=true}{if $similarAuthors.count} {$similarAuthors.count}{/if} &raquo;</strong></a>
			</div>
		{/if}
  </div>
{/if}