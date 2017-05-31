<div id="rfs-feal">

	<div class="rfs-feal-pages">

		<div class="rfs-feal-page rfs-feal-login active" id="login">
			<h2 class="text-center"><?php _e('Logging in', feal()->text_domain) ?></h2>

			<div class="msg-container"></div>

			<form class="rfs-feal-form" name="rfs-feal-form-login" id="rfs-feal-form-login" novalidate>
				<div class="form-group">
					<label for="feal-login"><?php _e('Login (username)', feal()->text_domain) ?></label>
					<input type="text" class="form-control" name="feal-login" id="feal-login">
				</div>
				<div class="form-group">
					<label for="feal-pass"><?php _e('Password', feal()->text_domain) ?></label>
					<input type="password" class="form-control" name="feal-pass" id="feal-pass">
				</div>
				<div class="form-group">
					<button type="button" class="btn btn-primary btn-lg"><?php _e('Log in', feal()->text_domain) ?></button>
					<div class="loader"></div>
				</div>
			</form>
			<?php if( feal()->enableRegistration ) : ?>
			<p><?php _e('Do not have an account yet?', feal()->text_domain) ?> <a href="#register"><?php _e('Register', feal()->text_domain) ?></a></p>
			<?php endif; ?>
			<p><?php _e('Forgot your password?', feal()->text_domain) ?> <a href="#remind"><?php _e('Remind', feal()->text_domain) ?></a></p>
		</div>

		<?php if( feal()->enableRegistration ) : ?>
		<div class="rfs-feal-page rfs-feal-registration" id="register">
			<h2 class="text-center"><?php _e('Registration', feal()->text_domain) ?></h2>

			<div class="msg-container"></div>

			<form class="rfs-feal-form" name="rfs-feal-form-register" id="rfs-feal-form-register" novalidate>
				<div class="form-group">
					<label for="feal-username"><?php _e('Username', feal()->text_domain) ?></label>
					<input type="text" class="form-control" name="feal-username" id="feal-username">
				</div>
				<div class="form-group">
					<label for="feal-email"><?php _e('Email address', feal()->text_domain) ?></label>
					<input type="email" class="form-control" name="feal-email" id="feal-email">
				</div>
				<div class="form-group">
					<button type="button" class="btn btn-primary btn-lg"><?php _e('Register', feal()->text_domain) ?></button>
					<div class="loader"></div>
				</div>
			</form>
			<p><?php _e('Have an account already?', feal()->text_domain) ?> <a href="#login"><?php _e('Log in', feal()->text_domain) ?></a></p>
		</div>
		<?php endif; ?>

		<div class="rfs-feal-page rfs-feal-password" id="remind">
			<h2 class="text-center"><?php _e('Password remind', feal()->text_domain) ?></h2>

			<div class="msg-container"></div>

			<form class="rfs-feal-form" name="rfs-feal-form-remind" id="rfs-feal-form-remind" novalidate>
				<div class="form-group">
					<label for="feal-user"><?php _e('Username or email', feal()->text_domain) ?></label>
					<input type="email" class="form-control" name="feal-user" id="feal-user">
				</div>
				<div class="form-group">
					<button type="button" class="btn btn-primary btn-lg"><?php _e('Remind', feal()->text_domain) ?></button>
					<div class="loader"></div>
				</div>
			</form>
			<p><a href="#login"><?php _e('Log in', feal()->text_domain) ?></a></p>
		</div>

		<div class="rfs-feal-page rfs-feal-setpassword" id="setpassword">
			
			<?php if( feal()->setPasswordType == 'activate' ) : ?>
				<h2 class="text-center"><?php _e('Account activation', feal()->text_domain) ?></h2>
			<?php endif; ?>

			<?php if( feal()->setPasswordType == 'remind' ) : ?>
				<h2 class="text-center"><?php _e('Set new password', feal()->text_domain) ?></h2>
			<?php endif; ?>

			<?php if( absint( $this->check_password_key() > 0 ) ) : ?>

				<?php if( feal()->setPasswordType == 'activate' ) : ?>
					<div class="msg-container">
						<div class="alert alert-success text-center"><?php _e('Your account has been activated. Set your new password below', feal()->text_domain) ?></div>
					</div>
				<?php else : ?>
					<div class="msg-container"></div>
				<?php endif; ?>

				<form class="rfs-feal-form" name="rfs-feal-form-setpassword" id="rfs-feal-form-setpassword" novalidate>
					<div class="form-group">
						<label for="feal-new-pass"><?php _e('Password', feal()->text_domain) ?></label>
						<input type="password" class="form-control" name="feal-new-pass" id="feal-new-pass" autocomplete="new-password">
					</div>
					<div class="form-group">
						<label for="feal-new-pass-repeat"><?php _e('Repeat password', feal()->text_domain) ?></label>
						<input type="password" class="form-control" name="feal-new-pass-repeat" id="feal-new-pass-repeat" autocomplete="new-password">
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
						<input class="form-control" type="hidden" name="feal-set-pass-user" value="<?php echo absint( $this->check_password_key() ); ?>">
						<button type="button" class="btn btn-primary btn-lg"><?php _e('Set password', feal()->text_domain) ?></button>
						<div class="loader"></div>
					</div>
				</form>

			<?php else : ?>

				<div class="msg-container">
					<div class="alert alert-danger text-center"><?php _e('The link is either expired or invalid', feal()->text_domain) ?></div>
				</div>

			<?php endif; ?>

		</div>

		<div class="rfs-feal-page rfs-passwordchanged" id="passwordchanged">
			<h2 class="text-center"><?php _e('Password change', feal()->text_domain) ?></h2>

			<div class="msg-container">
				<div class="alert alert-success text-center"><?php _e('Your password has been changed. Log in using the link below', feal()->text_domain) ?></div>
			</div>

			<p class="text-center"><a class="btn btn-primary btn-lg" href="#login"><?php _e('Log in', feal()->text_domain) ?></a></p>
		</div>

	</div>

</div>
