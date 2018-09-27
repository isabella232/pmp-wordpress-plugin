<div class="wrap">
	<h2>PMP Settings</h2>

	<?php settings_errors(); ?>

	<form action="<?php echo admin_url('options.php'); ?>" method="post">
		<?php settings_fields('pmp_settings_fields'); ?>
		<?php do_settings_sections('pmp_settings'); ?>
		<p class="submit">
			<input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes">
		</p>
	</form>
</div>
