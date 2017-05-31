<?php $data = $this->user_profile_data(); ?>
<div id="rfs-feal">
	
	<h2 class="text-center"><?php _e('Account details', feal()->text_domain) ?></h2>

	<div class="profile-details" id="profile">

		<?php if( $data ) : ?>
		
		<div class="msg-container"></div>

		<form class="rfs-feal-form" name="rfs-feal-form-profile" id="rfs-feal-form-profile" novalidate>
			<div class="form-group">
				<label for="feal-profile-login"><?php _e('Login (cannot be changed)', feal()->text_domain) ?></label>
				<input type="text" class="form-control" name="feal-profile-login" id="feal-profile-login" value="<?php echo esc_html( $data->user_login ); ?>" readonly>
			</div>
			<div class="form-group">
				<label for="feal-profile-email"><?php _e('Email', feal()->text_domain) ?></label>
				<input type="email" class="form-control" name="feal-profile-email" id="feal-profile-email" value="<?php echo esc_html( $data->user_email ); ?>">
			</div>
			<div class="form-group">
				<label for="feal-profile-new-pass"><?php _e('Password', feal()->text_domain) ?></label>
				<input type="password" class="form-control" name="feal-profile-new-pass" id="feal-profile-new-pass" autocomplete="new-password">
			</div>
			<div class="form-group">
				<label for="feal-profile-new-pass-repeat"><?php _e('Repeat password', feal()->text_domain) ?></label>
				<input type="password" class="form-control" name="feal-profile-new-pass-repeat" id="feal-profile-new-pass-repeat" autocomplete="new-password">
			</div>
			<div class="form-group">
				<p class="password-hint rfs-feal-icon rfs-feal-icon-info rfs-feal-icon-sm"><?php _e('Password hint', feal()->text_domain) ?></p>
				<div class="password-conditions">
					<p class=""><?php _e('Password must contain:', feal()->text_domain) ?></p>
					<ul class="list-unstyled">
						<li><?php _e('at least 8 characters', feal()->text_domain) ?></li>
						<li><?php _e('at least one lower case letter', feal()->text_domain) ?></li>
						<li><?php _e('at least one uppercase letter', feal()->text_domain) ?></li>
						<li><?php _e('at least one number', feal()->text_domain) ?></li>
						<li><?php _e('at least one special character', feal()->text_domain) ?></li>
					</ul>
				</div>
			</div>
			<div class="form-group">
				<button type="button" class="btn btn-primary btn-lg"><?php _e('Save', feal()->text_domain) ?></button>
				<div class="loader"></div>
			</div>
		</form>

		<?php else : ?>

		<div class="alert alert-danger text-center"><?php _e('You have no access to this page', feal()->text_domain) ?></div>

		<?php endif; ?>

	</div>

</div>