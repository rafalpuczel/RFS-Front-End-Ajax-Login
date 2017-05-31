<div class="wrap">
	<h1>RFS Front End Ajax Login</h1>
	<?php settings_errors(); ?>

	<div class="nav-tab-wrapper">
		<a href="#general" class="nav-tab nav-tab-active"><?php _e('General', feal()->text_domain); ?></a>
		<a href="#login-lockdown" class="nav-tab"><?php _e('Login Lockdown', feal()->text_domain); ?></a>
	</div>

	<form method="post" action="options.php">
		<?php
			
			do_settings_sections( 'rfs-feal-settings' );
			settings_fields( 'rfs-feal-options' );
			
			submit_button();
		?>
	</form>
</div>