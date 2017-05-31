<?php $roles = get_editable_roles(); ?>

<?php if( $roles ) : ?>
	<?php foreach($roles as $role => $data) : ?>
	<input type="checkbox" name="rfs_feal_admin_access[]" value="<?php echo esc_html( $role ); ?>" <?php if( (is_array( feal()->admin_access_options ) && in_array( $role, feal()->admin_access_options )) || $role == 'administrator' ) : ?> checked="checked"<?php endif; ?> <?php if( $role == 'administrator' ) : ?>disabled="disabled"<?php endif; ?>><?php esc_html_e( $data['name'], feal()->text_domain ); ?><br>
	<?php endforeach; ?>
<?php endif; ?>