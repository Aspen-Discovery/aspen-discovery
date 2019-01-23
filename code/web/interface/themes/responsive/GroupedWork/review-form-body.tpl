{strip}
	<form class="form-horizontal" role="form">
		{if $showRatings}
		<div class="rateTitle form-group">
			<label for="rating" class="col-sm-3">Rate the Title</label>
			<div class="col-sm-9">
				<select name="rating" id="rating{$id}" class="form-control">
					<option value="-1">{translate text="Select a Rating"}</option>
					<option value="1"{if $userRating==1} selected="selected"{/if}>{translate text="rating1"}</option>
					<option value="2"{if $userRating==2} selected="selected"{/if}>{translate text="rating2"}</option>
					<option value="3"{if $userRating==3} selected="selected"{/if}>{translate text="rating3"}</option>
					<option value="4"{if $userRating==4} selected="selected"{/if}>{translate text="rating4"}</option>
					<option value="5"{if $userRating==5} selected="selected"{/if}>{translate text="rating5"}</option>
				</select>
			</div>
		</div>
		{/if}
		{if $showComments}
		<div class="form-group">
			<label for="comment{$id}" class="col-sm-3">Write a Review</label>
			<div class="col-sm-9">
				<textarea name="comment" id="comment{$id}" rows="4" cols="60" class="form-control">
					{$userReview}
				</textarea>
			</div>
		</div>
		{/if}
	</form>
{/strip}