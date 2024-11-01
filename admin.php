<?php
	/**
	 * Thumbnailr for Wordpress; thumbnailr.com
	 */
	if (is_admin()) {
		add_action('admin_init', 'thumbnailr_admin_init');
		add_action('admin_menu', 'thumbnailr_admin_menu');
	}

	function thumbnailr_admin_menu() {
		add_options_page('Thumbnailr Options', 'Thumbnailr', 'manage_options', 'thumbnailr-options', 'thumbnailr_plugin_options');
	}

	function thumbnailr_admin_init() {
		add_filter('admin_footer_text', '__return_false', 11);
		add_filter('update_footer', '__return_false', 11);
	}


	function thumbnailr_plugin_options() {
		if (!current_user_can('manage_options')) {
			wp_die(__('You do not have sufficient permissions to access this page.'));
		}

		include_once "admin-misc.php";
		include_once COZYUNI_DIR . "cozyuni-admin-bind.php";

		$cozyuni_apiKey = isset($_POST["cozyuni_apikey"]) ? sanitize_text_field($_POST["cozyuni_apikey"]) : cozyuni_getApiKey();
		$thumbnailr_email = isset($_POST["thumbnailr_email"]) ? sanitize_email($_POST["thumbnailr_email"]) : "";
		$thumbnailr_pass = isset($_POST["thumbnailr_pass"]) ? sanitize_text_field($_POST["thumbnailr_pass"]) : "";
		if (isset($_POST['bind']) && check_admin_referer('bind_clicked')) {
			cozyuni_admin_bind(function () {
				cozyuni_setAdminMsg("Wordpress is now connected to your Thumbnailr site. Api key is also saved.");
			}, cozyuni_getErrorListener(), $cozyuni_apiKey, $thumbnailr_email, $thumbnailr_pass, "");
		}

		if (isset($_POST['unbind']) && check_admin_referer('unbind_clicked')) {
			$removeUsers = isset($_POST["removeusers"]) ? ($_POST["removeusers"] == "1" ? "1" : "0") : "0";
			cozyuni_admin_unbind(function () {
				cozyuni_setAdminMsg("Wordpress has been disconnected from your Thumbnailr site.");
			}, cozyuni_getErrorListener(), $removeUsers);
		}

		if (isset($_POST['clearcache']) && check_admin_referer('clearcache_clicked')) {
			thumbnailr_admin_clearCache();
		}

		$savedTrApiKey = cozyuni_getApiKey();
		?>
		<div class="wrap">
			<h1>Thumbnailr settings</h1>


			<?php cozyuni_printAdminMsg() ?>

			<table id="main-table">
				<tr>
					<td>


						<table class="form-table">
							<tbody>
							<tr>
								<th>Setup status</th>
								<td>
									<!-- --------------------------------------- steps ------------------------------------------------------------ -->
									<ul>
										<?php
											$baseUrl = cozyuni_getBaseUrl();
											$connected = (!($baseUrl === false) && $savedTrApiKey != "");
											cozyuni_printStep("Connect to Thumbnailr", $connected);
										?>
									</ul>
								</td>
							</tr>
							<tr>
								<th>Connect your Wordpress with Thumbnailr</th>
								<td>
									<!-- --------------------------------------- connect ------------------------------------------------------------ -->

									<p>Fill out the form below and press the 'Connect' button to connect this Wordpress installation to your

										<a href="http://www.thumbnailr.com" target="_blank">Thumbnailr site</a>.

										On success this will save your Api key and will auto configure your Thumbnailr site to work correctly with this Wordpress.</p>

									<form action="options-general.php?page=thumbnailr-options" method="post">
										<table class="form-table compact-form-table">
											<tr valign="top">
												<th scope="row">Connected</th>
												<td class="<?= $connected ? "cozyuni_done" : "cozyuni_notdone" ?>">
													<?php
														if ($connected) {
															print 'Yes, with: <a href="' . esc_url($baseUrl) . '" target="_blank">' . esc_url($baseUrl) . '</a>';
														} else {
															print "No";
														}
													?>

												</td>
											</tr>
											<tr>
												<td colspan="2">
													<hr>
												</td>
											</tr>
											<tr valign="top">
												<th scope="row">Thumbnailr api key</th>
												<td>
													<input type="text" name="cozyuni_apikey" value="<?= esc_attr($cozyuni_apiKey) ?>" class="regular-text" required>
												</td>
											</tr>
											<tr valign="top">
												<th scope="row">Thumbnailr webmaster email address</th>
												<td>
													<input type="text" name="thumbnailr_email" value="<?= esc_attr($thumbnailr_email) ?>" class="regular-text" required>
												</td>
											</tr>
											<tr valign="top">
												<th scope="row">Thumbnailr webmaster password</th>
												<td>
													<input type="password" name="thumbnailr_pass" value="<?= esc_attr($thumbnailr_pass) ?>" class="regular-text" required>
												</td>
											</tr>
										</table>

										<?php wp_nonce_field('bind_clicked'); ?>
										<input type="hidden" value="true" name="bind"/>
										<?php submit_button('Connect with Thumbnailr') ?>
									</form>
								</td>
							</tr>
							<?php if ($savedTrApiKey != "") { ?>
								<tr>
									<th>Clear cache</th>
									<td>
										<!-- --------------------------------------- cache ------------------------------------------------------------ -->
										<p>Most data used by this plugin, for example in widgets, is cached for a small amount of time.</p>

										<p>You shouldn't have to manually clear the cache, but if you want you can do it by simply clicking the 'Clear cache' button.</p>

										<form action="options-general.php?page=thumbnailr-options" method="post">
											<?php wp_nonce_field('clearcache_clicked'); ?>
											<input type="hidden" value="true" name="clearcache"/>
											<?php submit_button('Clear cache') ?>
										</form>
									</td>
								</tr>
								<tr>
									<th>Disconnect</th>
									<td>
										<!-- --------------------------------------- disconnect ------------------------------------------------------------ -->

										<h2 class="title"></h2>

										<p>If you don't want to use your Thumbnailr site in your Wordpress anymore than please click the Disconnect button below.</p>

										<p>Note that you can always 'Connect' again.</p>
										<form action="options-general.php?page=thumbnailr-options" method="post">
											<?php wp_nonce_field('unbind_clicked'); ?>
											<input type="hidden" value="true" name="unbind"/>
											<?php submit_button('Disconnect from Thumbnailr') ?>
										</form>

									</td>
								</tr>
							<?php } ?>
							</tbody>
						</table>

					</td>
					<td width="200">
						<div id="cozyuni_brand">
							<div align="center" style="background-color: black;"><a href="http://www.thumbnailr.com/" target="_blank"><img
										src="<?= plugins_url("logo.png", __FILE__) ?>"></a></div>
							<hr>
							<b>Handy links</b>
							<BR>
							<a href="http://support.thumbnailr.com/pages/wordpress-plugin-installation-manual.html">Installation &amp configuration Manual</a>
							<BR>
							<a href="http://www.thumbnailr.com/" target="_blank">Thumbnailr</a>
							<BR>
							<a href="http://www.thumbnailr.com/dash/preaccount/add" target="_blank">Create a free account</a>
							<BR>
							<a href="http://support.thumbnailr.com/" target="_blank">Support forum</a>
							<BR>
							<b>Tips</b>
							<BR>
							* 'Recent images' widget is available.
							<BR>

						</div>
					</td>
				</tr>
			</table>

		</div>


		<?php
		cozyuni_printAdminCss();
	}



