<script type="text/html" id="table-listing-string">

	<th scope="row" class="check-column">
		<%	if(TJ.translation_service == 'local') { %>
			<input type="checkbox" class="js-selected-items" name="icl_translation_id[]" value="<%= 'string|' + TJ.translation_id %>">
		<% } %>
	</th>
	<td class="job_id column-job_id"><%= TJ.id %></td>
	<td class="title column-title"><%= TJ.name %>: <strong><%= TJ.value %></strong></td>
	<td class="language column-language"><%= TJ.lang_text %></td>
	<td class="status column-status"><%= TJ.status %></td>
	<td class="translator column-translator"><%= TJ.translator_html %></td>

</script>
