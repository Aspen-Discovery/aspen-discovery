{strip}
	<form class="form-horizontal" role="form">
		{if $showRatings}
		<div class="rateTitle form-group">
			<label for="rating" class="col-sm-3">{translate text="Rate the Title" isPublicFacing=true}</label>
			<div class="col-sm-9">
				<select name="rating" id="rating{$id}" class="form-control">
					<option value="-1">{translate text="Select a Rating" isPublicFacing=true inAttribute=true}</option>
					<option value="1"{if $userRating==1} selected="selected"{/if}>{translate text="1 - Hated It" isPublicFacing=true inAttribute=true}</option>
					<option value="2"{if $userRating==2} selected="selected"{/if}>{translate text="2 - Didn't Like It" isPublicFacing=true inAttribute=true}</option>
					<option value="3"{if $userRating==3} selected="selected"{/if}>{translate text="3 - Liked It" isPublicFacing=true inAttribute=true}</option>
					<option value="4"{if $userRating==4} selected="selected"{/if}>{translate text="4 - Really Liked It" isPublicFacing=true inAttribute=true}</option>
					<option value="5"{if $userRating==5} selected="selected"{/if}>{translate text="5 - Loved It" isPublicFacing=true inAttribute=true}</option>
				</select>
			</div>
		</div>
		{/if}
		{if $showComments}
		<div class="form-group">
			<label for="comment{$id}" class="col-sm-3">{translate text="Write a Review" isPublicFacing=true}</label>
			<div class="col-sm-9">
				<textarea name="comment" id="comment{$id}" rows="4" cols="60" class="form-control">
					{$userReview}
				</textarea>
			</div>
		</div>
		{/if}
	</form>
{/strip}