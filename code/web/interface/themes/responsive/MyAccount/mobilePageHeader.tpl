{strip}
	{* taken from MyAccount/menu.tpl*}
	{* id attributes have prefix 'mobileHeader-' added *}
	<div class="row visible-xs">
		<div id="mobileHeader" class="col-tn-12 col-xs-12">

			<div id="mobileHeader-myAccountFines">
				<span class="expirationFinesNotice-placeholder"></span>
			</div>

			{* taken from MyAccount/menu.tpl*}
			<div class="myAccountLink{if $action=="CheckedOut"} active{/if}">
				<a href="{$path}/MyAccount/CheckedOut" id="mobileHeader-checkedOut">
					Checked Out Titles {if !$offline}<span class="checkouts-placeholder"><img src="{$path}/images/loading.gif" alt="loading"></span>{/if}
				</a>
			</div>
			<div class="myAccountLink{if $action=="Holds"} active{/if}">
				<a href="{$path}/MyAccount/Holds" id="mobileHeader-holds">
					Titles On Hold {if !$offline}<span class="holds-placeholder"><img src="{$path}/images/loading.gif" alt="loading"></span>{/if}
				</a>
			</div>

			{if $enableMaterialsBooking}
				<div class="myAccountLink{if $action=="Bookings"} active{/if}">
					<a href="{$path}/MyAccount/Bookings" id="mobileHeader-bookings">
						Scheduled Items  {if !$offline}<span class="bookings-placeholder"><img src="{$path}/images/loading.gif" alt="loading"></span>{/if}
					</a>
				</div>
			{/if}
			<div class="myAccountLink{if $action=="ReadingHistory"} active{/if}">
				<a href="{$path}/MyAccount/ReadingHistory">
					Reading History {if !$offline}<span class="readingHistory-placeholder"><img src="{$path}/images/loading.gif" alt="loading"></span>{/if}
				</a>
			</div>

			<hr>
		</div>
	</div>

{/strip}