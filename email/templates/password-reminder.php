<?php  
$styles 	= isset($params['styles']) ? $params['styles'] : '';
$link 		= isset($params['link']) ? $params['link'] : '';
$username 	= isset($params['username']) ? $params['username'] : '';
?>
<!doctype html>
<html>
	<head>
	<meta name="viewport" content="width=device-width" />
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<title>Registration</title>
	<?php echo $styles; ?>
	</head>
	<body class="">
		<table border="0" cellpadding="0" cellspacing="0" class="body">
			<tr>
			<td>&nbsp;</td>
			<td class="container">
				<div class="content">

				<!-- START CENTERED WHITE CONTAINER -->
				<span class="preheader"><?php _e('You have requested a password reminder', feal()->text_domain) ?></span>
				<table class="main">

					<!-- START MAIN CONTENT AREA -->
					<tr>
					<td class="wrapper">
						<table border="0" cellpadding="0" cellspacing="0">
						<tr>
							<td>
							<p><?php _e('Hello', feal()->text_domain); ?> <?php echo $username; ?></p>
							<p><?php _e('You have requested a password reminder', feal()->text_domain) ?></p>
							<p><?php _e('If you received this email by mistake, you can simply ignore it.', feal()->text_domain) ?></p>

							<br>
							<p><?php _e('In order to set a new password click the button below', feal()->text_domain) ?></p>
							<table border="0" cellpadding="0" cellspacing="0" class="btn btn-primary">
								<tbody>
								<tr>
									<td align="left">
									<table border="0" cellpadding="0" cellspacing="0">
										<tbody>
										<tr>
											<td> <a href="<?php echo esc_url( $link ); ?>" target="_blank"><?php _e('Set a new password', feal()->text_domain) ?></a> </td>
										</tr>
										</tbody>
									</table>
									</td>
								</tr>
								</tbody>
							</table>
							<p><?php _e('Alternatively you can paste the link below into the browser\'s address bar', feal()->text_domain) ?></p>
							<p><?php echo esc_url( $link ); ?></p>
							</td>
						</tr>
						</table>
					</td>
					</tr>

				<!-- END MAIN CONTENT AREA -->
				</table>

				<!-- START FOOTER -->
				<div class="footer">
					<table border="0" cellpadding="0" cellspacing="0">
					<tr>
						<td class="content-block">
							<span><?php _e('Best regards', feal()->text_domain) ?></span>
							<br>
							<span><?php bloginfo( 'name' ); ?></span>
						</td>
					</tr>
					</table>
				</div>
				<!-- END FOOTER -->
				
				<!-- END CENTERED WHITE CONTAINER -->
				</div>
			</td>
			<td>&nbsp;</td>
			</tr>
		</table>
	</body>
</html>