{strip}
    {* Overall hold *}
    <div class="row ilsHold_{$record->sourceId|escapeCSS}_{$record->cancelId|escapeCSS} row-no-gutters" style="padding:0; padding-bottom: 2em">
        {* Cover column *}
        {if $showCovers}
            <div class="hidden-xs hidden-sm col-md-3">
                <div class="{*col-xs-10 *}text-center">
                    {if !empty($record->getCoverUrl())}
                        {if !empty($record->getLinkUrl())}
                            <a href="{$record->getLinkUrl()}" id="descriptionTrigger{$record->recordId|escape:"url"}" aria-hidden="true" target="_blank">
                                <img src="{$record->getCoverUrl()}" class="listResultImage img-thumbnail img-responsive {$coverStyle}" alt="{translate text='Cover Image' inAttribute=true isPublicFacing=true}">
                            </a>
                        {else} {* Cover Image but no Record-View link *}
                            <img src="{$record->getCoverUrl()}" class="listResultImage img-thumbnail img-responsive {$coverStyle}" alt="{translate text='Cover Image' inAttribute=true isPublicFacing=true}" aria-hidden="true">
                        {/if}
                    {/if}

                </div>
            </div>
        {/if}

        {* Details Column*}
        <div class="col-sm-12 col-md-9" style="padding-left: 0">
            {* Title *}
            <div class="col-xs-12">
                <span class="result-index">{$resultIndex}) </span>
                {if $record->getLinkUrl()}
                    <a href="{$record->getLinkUrl()}" target="_blank" class="result-title notranslate">
                        {if !$record->getTitle()|removeTrailingPunctuation} {translate text='Title not available' isPublicFacing=true}{else}{$record->getTitle()|removeTrailingPunctuation|truncate:180:"..."|highlight}{/if}
                    </a>
                {else}
                    <span class="result-title notranslate">
                        {if !$record->getTitle()|removeTrailingPunctuation} {translate text='Title not available' isPublicFacing=true}{else}{$record->getTitle()|removeTrailingPunctuation|truncate:180:"..."|highlight}{/if}
                    </span>
                {/if}
                {if !empty($record->title2)}
                    <div class="searchResultSectionInfo">
                        {$record->title2|removeTrailingPunctuation|truncate:180:"..."|highlight}
                    </div>
                {/if}
            </div>

                {* Information column author, format, etc *}
                <div class="resultDetails col-xs-12">
                    {if !empty($record->volume)}
                        <div class="row" style="padding:0">
                            <div class="result-label col-sm-12 col-md-5">{translate text='Volume' isPublicFacing=true}</div>
                            <div class="col-sm-12 col-md-7 result-value">
                                {$record->volume}
                            </div>
                        </div>
                    {/if}

                    {if !empty($record->getAuthor())}
                        <div class="row" style="padding:0">
                            <div class="result-label col-sm-12 col-md-5">{translate text='Author' isPublicFacing=true}</div>
                            <div class="col-sm-12 col-md-7 result-value">
                                {if is_array($record->getAuthor())}
                                    {foreach from=$record->getAuthor() item=author}
                                        <a href='/Author/Home?"author={$author|escape:"url"}"'>{$author|highlight}</a>
                                    {/foreach}
                                {else}
                                    <a href='/Author/Home?author="{$record->getAuthor()|escape:"url"}"'>{$record->getAuthor()|highlight}</a>
                                {/if}
                            </div>
                        </div>
                    {/if}

                    {if !empty($record->getFormats())}
                        <div class="row" style="padding:0">
                            <div class="result-label col-sm-12 col-md-5">{translate text='Format' isPublicFacing=true}</div>
                            <div class="col-sm-12 col-md-7 result-value">
                                {implode subject=$record->getFormats() glue=", " translate=true isPublicFacing=true}
                            </div>
                        </div>
                    {/if}

                    <div class="row" style="padding:0">
                        <div class="result-label col-sm-12 col-md-5">{translate text='Pickup Location' isPublicFacing=true}</div>
                        <div class="col-sm-12 col-md-7 result-value">
                            {$record->pickupLocationName}
                        </div>
                    </div>

                    {if $showPlacedColumn && $record->createDate}
                        <div class="row" style="padding:0">
                            <div class="result-label col-sm-12 col-md-5">{translate text='Date Placed' isPublicFacing=true}</div>
                            <div class="col-sm-12 col-md-7 result-value">
                                {$record->createDate|date_format:"%b %d, %Y"}
                            </div>
                        </div>
                    {/if}

                    {if $record->expirationDate}
                        <div class="row" style="padding:0">
                            <div class="result-label col-sm-12 col-md-5">{translate text='Pickup By' isPublicFacing=true}</div>
                            <div class="col-sm-12 col-md-7 result-value">
                                <strong>{$record->expirationDate|date_format:"%b %d, %Y"}</strong>
                            </div>
                        </div>
                    {/if}

            </div>
        </div>
    </div>
{/strip}