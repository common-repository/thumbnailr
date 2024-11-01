<?php
	/**
	 * Thumbnailr for Wordpress; thumbnailr.com
	 */

	class Thumbnailr_RecentImages_Widget extends WP_Widget {
		function __construct() {
			parent::__construct(
				'thumbnailr_recentimages_widget', // Base ID
				esc_html__('Recent images'), // Name
				array('description' => esc_html__('Thumbnailr recent images'),) // Args
			);
		}

		public function widget($args, $instance) {
			$recentimages= cozyuni_getCached("recentimages");

			if (!is_array($recentimages)) {
				$max = isset($instance['max'])?$instance["max"]:25;
				$errorMsg = "";

				$resp = cozyuni_remote_get("/api/recentimages?&max=" . $max);
				if (!is_array($resp)) {
					$errorMsg = $resp;
				} else {
					$recentimages = $resp["images"];

					$uploadDir = wp_upload_dir();

					//create /wp-content/uploads/thumbnailr/ folder
					$baseUploadDir = $uploadDir["basedir"];
					$baseUploadDir .= "/thumbnailr/";
					if (!file_exists($baseUploadDir)) {
						mkdir($baseUploadDir);
					}

					$addedFiles = array();
					$localImages=array();
					foreach ($recentimages as $image) {
						write_log("downloading: ".$image["thumbUrl"]);
						//download image
						$tmpfile = $this->thumbnailr_download_url($image["thumbUrl"], $timeout = 300);
						if(is_wp_error($tmpfile)){
							write_log("error: ".$tmpfile->get_error_message());
							continue;
						}
						//save image to /wp-content/uploads/thumbnailr/ folder
						$fn = $image["id"] . "." . $image["ext"];
						$permFile = $baseUploadDir . $fn;
						copy($tmpfile, $permFile);
						unlink($tmpfile);

						//add to local images
						$image["thumbUrl"] = $uploadDir["baseurl"] . "/thumbnailr/" . $fn;
						array_push($localImages, $image);

						//record added file
						$addedFiles[$fn] = true;
					}

					//set images to local images
					$recentimages["images"]=$localImages;

					//remove old not used images from upload folder
					$filesInDir = scandir($baseUploadDir);
					foreach ($filesInDir as $key => $value) {
						if(!is_file($value)){
							continue;
						}
						if (!isset($addedFiles[$value])) {
							//remove
							unlink($baseUploadDir . $value);
						}
					}

					cozyuni_setCached("recentimages", $recentimages);
				}
			}
			print $args['before_widget'];

			if (!empty($instance['title'])) {
				print $args['before_title'] . apply_filters('widget_title', $instance['title']) . $args['after_title'];
			}

			if (!empty($errorMsg)) {
				print $errorMsg;
			} else {
				$thumbSize = isset($instance['thumbsize'])?$instance["thumbsize"]:100;
				?>
				<style>
					.thumbnailr-img-grid {
						display: flex;
						flex-direction: row;
						flex-wrap: wrap;
						justify-content: flex-start;
					}

					.thumbnailr-img-grid .thumbnailr-img-grid-cell {
						width: <?= $thumbSize ?>px;
						height: <?= $thumbSize ?>px;
						display: flex;
						justify-content: center;
						align-items: center;
						overflow: hidden;
						margin: 0.25rem;
					}

					.thumbnailr-img-grid .thumbnailr-img-grid-cell img {
						width: 100%;
						height: auto;
					}
					.thumbnailr-img-grid-cell {
						position: relative;
						border: 0px solid lightgrey;
					}
				</style>
				<div class="thumbnailr-img-grid">
					<?php
						foreach ($recentimages["images"] as $image) {
							?>
							<div class="thumbnailr-img-grid-cell">
							<a href="<?= $image["detailsUrl"] ?>" title="<?= $image["title"] ?>"><img src="<?= $image["thumbUrl"] ?>"></a>
							</div>
							<?php
						}
					?>
				</div>
				<?php
			}

			print $args['after_widget'];
		}


		public function form($instance) {
			$title = !empty($instance['title']) ? $instance['title'] : esc_html__('Recent images');
			$max = !empty($instance['max']) ? $instance['max'] : esc_html__('25');
			$thumbsize = !empty($instance['thumbsize']) ? $instance['thumbsize'] : esc_html__('100');
			?>
			<p>
				<label for="<?= esc_attr($this->get_field_id('title')); ?>"><?= esc_attr_e('Title:'); ?></label>

				<input class="widefat" id="<?= esc_attr($this->get_field_id('title')); ?>" name="<?= esc_attr($this->get_field_name('title')); ?>" type="text"
				       value="<?= esc_attr($title); ?>">
			</p>

			<p>
				<label for="<?= esc_attr($this->get_field_id('max')); ?>"><?= esc_attr_e('Number of images:'); ?></label>

				<input class="widefat" id="<?= esc_attr($this->get_field_id('max')); ?>" name="<?= esc_attr($this->get_field_name('max')); ?>" type="text"
				       value="<?= esc_attr($max); ?>">
			</p>

			<p>
				<label for="<?= esc_attr($this->get_field_id('max')); ?>"><?= esc_attr_e('Thumbnail size in pixels:'); ?></label>

				<input class="widefat" id="<?= esc_attr($this->get_field_id('thumbsize')); ?>" name="<?= esc_attr($this->get_field_name('thumbsize')); ?>" type="text"
				       value="<?= esc_attr($thumbsize); ?>">
			</p>
			<?php
		}


		public function update($new_instance, $old_instance) {
			$instance = array();
			$instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
			$instance['max'] = (!empty($new_instance['max']) && is_numeric($new_instance['max'])) ? sanitize_text_field($new_instance['max']) : '25';
			$instance['thumbsize'] = (!empty($new_instance['thumbsize']) && is_numeric($new_instance['thumbsize'])) ? sanitize_text_field($new_instance['thumbsize']) : '100';
			cozyuni_clearCached("recentimages");

			return $instance;
		}

		function thumbnailr_wp_tempnam( $filename = '', $dir = '' ) {
			if ( empty( $dir ) ) {
				$dir = get_temp_dir();
			}

			if ( empty( $filename ) || '.' == $filename || '/' == $filename || '\\' == $filename ) {
				$filename = uniqid();
			}

			// Use the basename of the given file without the extension as the name for the temporary directory
			$temp_filename = basename( $filename );
			$temp_filename = preg_replace( '|\.[^.]*$|', '', $temp_filename );

			// If the folder is falsey, use its parent directory name instead.
			if ( ! $temp_filename ) {
				return wp_tempnam( dirname( $filename ), $dir );
			}

			// Suffix some random data to avoid filename conflicts
			$temp_filename .= '-' . wp_generate_password( 6, false );
			$temp_filename .= '.tmp';
			$temp_filename  = $dir . wp_unique_filename( $dir, $temp_filename );

			$fp = @fopen( $temp_filename, 'x' );
			if ( ! $fp && is_writable( $dir ) && file_exists( $temp_filename ) ) {
				return wp_tempnam( $filename, $dir );
			}
			if ( $fp ) {
				fclose( $fp );
			}

			return $temp_filename;
		}

		public function thumbnailr_download_url($url, $timeout = 300) {
			if (!$url) {
				return new WP_Error('http_no_url', __('Invalid URL Provided.'));
			}

			$url_filename = basename(parse_url($url, PHP_URL_PATH));

			$tmpfname = $this->thumbnailr_wp_tempnam($url_filename);
			if (!$tmpfname) {
				return new WP_Error('http_no_file', __('Could not create Temporary file.'));
			}

			$response = wp_remote_get(
				$url,
				array(
					'timeout' => $timeout,
					'stream' => true,
					'filename' => $tmpfname,
				)
			);

			if (is_wp_error($response)) {
				unlink($tmpfname);
				return $response;
			}

			$response_code = wp_remote_retrieve_response_code($response);

			if (200 != $response_code) {
				$data = array(
					'code' => $response_code,
				);
				unlink($tmpfname);
				return new WP_Error('http_404', trim(wp_remote_retrieve_response_message($response)), $data);
			}

			return $tmpfname;
		}

	}