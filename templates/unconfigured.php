<div class="wrap">
	<?php if ( isset( $title ) && ! empty( $title ) ) { ?>
		<h2><?php echo wp_kses_post( $title ); ?></h2>
	<?php } ?>

	<div id="pmp-incomplete-settings-notice">
		Please specify an <strong>API URL<strong>, <strong>Client ID</strong>, <strong>Client Secret</strong> via the <a href="<?php echo admin_url('admin.php?page=pmp-options-menu'); ?>">PMP settings page</a>.
	</div>
</div>
