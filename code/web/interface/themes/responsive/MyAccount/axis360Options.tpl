{strip}
	<div id="main-content">
		{if $loggedIn}
			{if !empty($profile->_web_note)}
				<div class="row">
					<div id="web_note" class="alert alert-info text-center col-xs-12">{$profile->_web_note}</div>
				</div>
			{/if}
			{if !empty($accountMessages)}
				{include file='systemMessages.tpl' messages=$accountMessages}
			{/if}

			<h1>{translate text='Axis 360 Options' isPublicFacing=true}</h1>
			{if $offline}
				<div class="alert alert-warning"><strong>{translate text=$offlineMessage isPublicFacing=true}</strong></div>
			{else}
				{* MDN 7/26/2019 Do not allow access for linked users *}
				{*				{include file="MyAccount/switch-linked-user-form.tpl" label="View Account Settings for" actionPath="/MyAccount/Axis360Options"}*}

				{* Empty action attribute uses the page loaded. this keeps the selected user patronId in the parameters passed back to server *}
				<form action="" method="post" class="form-horizontal">
					<input type="hidden" name="updateScope" value="overdrive">
					<div class="form-group">
						<div class="col-xs-4"><label for="axis360Email" class="control-label">{translate text='Axis 360 Hold email' isPublicFacing=true}</label></div>
						<div class="col-xs-8">
							{if $edit == true}<input name="axis360Email" id="axis360Email" class="form-control" value='{$profile->axis360Email|escape}' size='50' maxlength='75'>{else}{$profile->axis360Email|escape}{/if}
						</div>
					</div>
					<div class="form-group">
						<div class="col-xs-4"><label for="promptForAxis360Email" class="control-label">{translate text='Prompt for Axis 360 email' isPublicFacing=true}</label></div>
						<div class="col-xs-8">
                            {if $edit == true}
								<input type="checkbox" name="promptForAxis360Email" id="promptForAxis360Email" {if $profile->promptForAxis360Email==1}checked='checked'{/if} data-switch="">
                            {else}
                                {if $profile->promptForAxis360Email==0}{translate text="No" isPublicFacing=true}{else}{translate text="Yes" isPublicFacing=true}{/if}
                            {/if}

						</div>
						{if !$offline && $edit == true}
                            <div class="form-group">
                                <div class="col-xs-8 col-xs-offset-4">
                                    <button type="submit" name="updateAxis360" class="btn btn-primary">{translate text="Update Options" isPublicFacing=true}</button>
                                </div>
                            </div>
                        {/if}
                    </form>
					</div>
			{/if}
		{else}
			<div class="page">
				{translate text="You must sign in to view this information." isPublicFacing=true}<a href='/MyAccount/Login' class="btn btn-primary">{translate text="Sign In" isPublicFacing=true}</a>
			</div>
		{/if}
	</div>
{/strip}
