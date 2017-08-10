<script type="text/html" id="table-listing-navigator">
	<span style="padding-left:5em;float:right;">
		<div class="tablenav">
			<div class="tablenav-pages" style="overflow: hidden;white-space: nowrap;">
				<% if( !TJ.show_all ) { %>
					<a id="icl_jobs_show_all" style="width: auto; font-weight:normal" href="#"><?php _e("show all %s items", 'wpml-translation-management');?></a>
				<%} else {%>
					<a id="icl_jobs_show_all" style="width: auto; font-weight:normal" href="#"><?php _e("show %s items", 'wpml-translation-management');?></a>
				<% } %>
				<span class="displaying-num"></span>
				<a class="js-nav-prev-page-arrow" style="font-weight:normal" href="#">«</a>
				<a class="js-nav-first-page" style="font-weight:normal" href="#">1</a>
				<span class="js-nav-left-dots">...</span>
				<a class="js-nav-before-prev-page" style="font-weight:normal" href="#"></a>
				<a class="js-nav-prev-page" style="font-weight:normal" href="#"></a>
				<span class="page-numbers-current"></span>
				<a class="js-nav-next-page" style="font-weight:normal" href="#"></a>
				<a class="js-nav-after-next-page" style="font-weight:normal" href="#"></a>
				<span class="js-nav-right-dots">...</span>
				<a class="js-nav-last-page" style="font-weight:normal" href="#"></a>
				<a class="js-nav-next-page-arrow" style="font-weight:normal" href="#">»</a>
			</div>
		</div>
	</span>
</script>
