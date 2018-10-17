<div id="pmp-search-page" class="wrap">
	<h2>Search the Platform</h2>

	<?php if (isset($PMP['search'])) { ?>
	<div id="message" class="updated below-h2">
		<p>Viewing saved query: <strong>"<?php echo $PMP['search']->options->title; ?>"</strong></p>
	</div>
	<?php } ?>

	<?php if (pmp_verify_settings()) { ?>
		<form id="pmp-search-form">
			<input name="text" placeholder="Enter keywords" type="text"></input>
			<span id="pmp-show-advanced"><a href="#">Show advanced options</a></span>
			<div id="pmp-advanced-search">
				<div class="left">
					<?php do_action('pmp_search_before_primary'); ?>

					<!-- Creator search (editable dropdown w/ 5 partners) -->
					<label for="creator">Content creator:</label>
					<select name="creator">
						<option value="">Any</option>
						<?php foreach ($creators as $name => $guid) { ?>
						<option value="<?php echo $guid; ?>"><?php echo $name; ?></option>
						<?php } ?>
					</select>

					<!-- Profile search (static dropdown) -->
					<label for="profile">Content profile:</label>
					<select disabled name="profile">
						<?php foreach ($profiles as $name => $value) { ?>
						<option <?php if ($value == 'story') { ?>selected="selected"<?php } ?> value="<?php echo $value; ?>"><?php echo $name; ?></option>
						<?php } ?>
					</select>

					<!-- Has search (e.g., has image) (static dropdown) -->
					<div id="pmp-content-has-search">
						<label for="has">Find content that contains:</label>
						<select name="has">
							<option value="">Any media</option>
							<option value="image">Image</option>
							<option value="audio">Audio</option>
							<option value="video">Video</option>
						</select>
					</div>

					<?php do_action('pmp_search_after_primary'); ?>
				</div>
				<div class="right">
					<?php do_action('pmp_search_before_secondary'); ?>

					<!-- Collection search (text-field) -->
					<label for="collection">Search by collection GUID:</label>
					<input type="text" name="collection" placeholder="Search by collection GUID"></input>

					<!-- Tags search (text-field) -->
					<label for="tag">Search by tag (comma separated list):</label>
					<input type="text" name="tag" placeholder="Search by tag"></input>

					<!-- GUID search -->
					<label for="guid">Search by GUID:</label>
					<input type="text" name="guid" placeholder="Search by GUID"></input>

					<?php do_action('pmp_search_after_secondary'); ?>
				</div>
			</div>
			<p class="submit">
				<input type="submit" name="submit" id="submit" class="button button-primary button-large" value="Search"></input>
				<input type="button" name="save_query" id="pmp-save-query" class="button button-large"
					value="<?php if (isset($PMP['search'])) { ?>Edit<?php } else { ?>Save<?php } ?> query" disabled="disabled"></input>
				<span class="spinner"></span>
				<span class="pmp-total-results"></span>
			</p>
		</form>

		<div id="pmp-search-results"></div>
	<?php
		} else {
			pmp_render_template( 'unconfigured.php' );
		}
	?>
</div>

<script type="text/template" id="pmp-search-result-tmpl">
	<div class="pmp-search-result">
		<h3 class="pmp-title"><%= title %></h3>
		<div class="pmp-result-details">
			<div class="pmp-date-line">
				<span class="pmp-date">Published <%= date.toLocaleDateString() %> at <%= date.toLocaleTimeString() %></span>
			</div>
			<div class="pmp-byline">
				<% if (typeof byline != 'undefined' && byline != '') { %>By <%= byline %> | <% } %>
				<span class="pmp-creator"><%= creator %></span> |
				<span><a class="pmp-support-link" target="_blank" title="View this document on the PMP support site" href="<%= PMP.support_link_base %><%= guid %>"><%= guid.substr(0, 8) %><span class="ext-link dashicons dashicons-external"></span></a></span>
			</div>
			<% if (typeof teaser != 'undefined') { %>
				<div class="pmp-teaser">
					<% if (image) { %><img class="pmp-image" src="<%= image %>" /><% } %>
					<%= teaser %>
				</div>
			<% } else if (image) { %><img class="pmp-image" src="<%= image %>" /><% } %>
		</div>
		<% if (typeof _wp_edit_link !== 'undefined') { %>
			<div class="pmp-result-exists error">
				<p>This post has already been imported. <a href="<%= _wp_edit_link %>">Click here to edit.</a></p>
			</div>
		<% } else { %>
			<div class="pmp-result-actions">
				<ul>
					<li><a class="pmp-draft-action" href="#">Create draft</a></li>
					<li><a class="pmp-publish-action" href="#">Publish</a></li>
				</ul>
			</div>
		<% } %>
	</div>
</script>

<script type="text/template" id="pmp-search-results-pagination-tmpl">
	<div id="pmp-search-results-pagination">
		<a href="#" class="disabled prev button button-primary">Previous</a>
		<a href="#" class="disabled next button button-primary">Next</a>
		<span class="spinner"></span>
		<p class="pmp-page-count">Page <span class="pmp-page"></span> of <span class="pmp-total-pages"></span></p>
	</div>
</script>

<?php
if (!empty($PMP['search']))
	pmp_save_search_query_template($PMP['search']);
else
	pmp_save_search_query_template();

pmp_modal_underscore_template();
?>

<script type="text/javascript">
	var PMP = <?php echo json_encode($PMP); ?>;
</script>
