{if $libraryLinks}
	<div id="{$linksId}" class="sidebar-links accordion">
		<div class="panel-group" id="link-accordion">
			{foreach from=$libraryLinks item=linkCategory key=categoryName name=linkLoop}
				{if $categoryName}
					{* Put the links within a collapsible section *}
					<div class="panel {if $smarty.foreach.linkLoop.first && !$loggedIn}active{/if}">
						<a data-toggle="collapse" data-parent="#link-accordion" href="#{$categoryName|escapeCSS}{$section}Panel">
							<div class="panel-heading">
								<div class="panel-title">
									{$categoryName}
								</div>
							</div>
						</a>
						<div id="{$categoryName|escapeCSS}{$section}Panel" class="panel-collapse collapse {if ($smarty.foreach.linkLoop.first && !$loggedIn) || array_key_exists($categoryName, $expandedLinkCategories)}in{/if}">
							<div class="panel-body">
								{foreach from=$linkCategory item=link key=linkName}
									{if $link->htmlContents}
										{$link->htmlContents}
									{else}
										<div>
											<a href="{$link->url}">{$linkName}</a>
										</div>
									{/if}
								{/foreach}
							</div>
						</div>
					</div>
				{else}
					{* No category name, display these links as buttons *}
					{foreach from=$linkCategory item=link key=linkName}
						{if $link->htmlContents}
							{$link->htmlContents}
						{else}
							<a href="{$link->url}">
								<div class="sidebar-button custom-sidebar-button" id="{$linkName|escapeCSS|lower}-button">
									{$linkName}
								</div>
							</a>
						{/if}
					{/foreach}
				{/if}
			{/foreach}

		</div>
	</div>
{/if}