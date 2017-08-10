<script type="text/html" id="table-listing-post">

	<th scope="row" class="check-column">
	<%	if(TJ.translation_service == 'local' && TJ.status !== 'Complete') { %>
		<input type="checkbox" class="js-selected-items" name="icl_translation_id[]" value="<%= TJ.translation_id %>">
	<% } %>
	</th>
	<td class="job_id column-job_id"><%= TJ.id %></td>
	<td class="title column-title"><a href="<%= TJ.original_url %>"><%= TJ.post_title %></a></td>
	<td class="language column-language"><%= TJ.lang_text %></td>
	<td class="status column-status"><% if( TJ.status === 'Complete' ) {
		%><%= TJ.translation_edit_url ? ( '<a href="' + TJ.translation_edit_url + '">' + TJ.status + '</a>' ) : ( '<span>' + TJ.status + '</span>' ) %><%
	} else {
		print(TJ.status);
	 } %>
	</td>
	<td class="translator column-translator"><%= TJ.translator_html %></td>

</script>