{strip}
  <div id="main-content" class="col-md-12">
    <h2>Search Editorial Reviews</h2>
    <div id='searchOptions'>
      <form id='editorialReviewSearchOptions' action='{$path}/EditorialReview/Search'>
        <div id='sortOptions'><label for='sortOptions'>Sort by:</label>
          <select name='sortOptions' id='sortOptions'>
	          <option value="editorialReviewId" {if $sort=='editorialReviewId'}selected="selected"{/if}>Id</option>
						<option value="source" {if $sort=='source'}selected="selected"{/if}>Source</option>
						<option value="title" {if $sort=='title'}selected="selected"{/if}>Title</option>
						<option value="pubDate" {if $sort=='pubDate'}selected="selected"{/if}>Date</option>
          </select> 
        </div>
        <input type='submit' value='Update Search' name='submit'/>
      </form>
    </div>
    
		<table class="table table-bordered table-striped">
			<thead>
				<tr><th>Id</th><th>Source</th><th>Title</th><th>Date</th></tr>
			</thead>
			<tbody>
				{foreach from=$results item=result}
					<tr>
						<td>{$result->editorialReviewId}</td>
						<td><a href='{$path}/EditorialReview/{$result->editorialReviewId}/View'>{$result->source}</a></td>
						<td><a href='{$path}/EditorialReview/{$result->editorialReviewId}/View'>{$result->title}</a> ({$result->recordId})</td>
						<td>{$result->formattedPubDate()}</td>
					</tr>
				{/foreach}
			</tbody>
		</table>
    
    {if $pageLinks.all}<div class="pagination">Page: {$pageLinks.all}</div>{/if}
  </div>
{/strip}