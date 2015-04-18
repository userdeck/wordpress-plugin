<div id="userdeck-wrapper" class="wrap">
	<?php screen_icon( 'options-general' ); ?>
	<h2>UserDeck</h2>

	<p><a href="http://userdeck.com?utm_source=wordpress&utm_medium=link&utm_campaign=website" target="_blank">UserDeck</a> provides customer support software that embeds into your WordPress website.</p>
	
	<?php if ($show_guides_options): ?>
		<h2>Guides</h2>
		
		<div id="poststuff">
			<div class="postbox-container" style="width:65%;">
				<?php if (current_user_can('publish_pages')) : ?>
					<form method="post" action="options-general.php?page=userdeck">
						<div class="postbox">
							<h3 class="hndle" style="cursor: auto;"><span>Create a Page</span></h3>
							
							<div class="inside">
								<p>Create a new page with the Guides shortcode.</p>
								
								<table class="form-table">
									<tbody>
										<tr valign="top">
											<th scope="row">
												<label for="page-title">Page Title</label>
											</th>
											<td>
												<input name="page_title" type="text" value="" id="page-title" />
											</td>
										</tr>
									</tbody>
								</table>
								
								<p>
									<?php wp_nonce_field('userdeck-page-create'); ?>
									<input type="hidden" name="guides_key" value="<?php echo $guides_key ?>" />
									<input class="button-primary" name="userdeck-page-create" type="submit" value="Create Page" />
								</p>
							</div>
						</div>
					</form>
				<?php endif; ?>
				
				<?php if (current_user_can('edit_pages')) : ?>
					<?php if (count($pages) > 0): ?>
						<form method="post" action="options-general.php?page=userdeck">
							<div class="postbox">
								<h3 class="hndle" style="cursor: auto;"><span>Add to Page</span></h3>
								
								<div class="inside">
									<p>Add the Guides shortcode to an existing page.</p>
									
									<table class="form-table">
										<tbody>
											<tr valign="top">
												<th scope="row">
													<label for="page-id">Page Title</label>
												</th>
												<td>
													<select name="page_id" id="page-id">
														<?php foreach ($pages as $id => $title): ?>
															<option value="<?php echo $id ?>"><?php echo $title ?></option>
														<?php endforeach; ?>
													</select>
												</td>
											</tr>
										</tbody>
									</table>
									
									<p>
										<?php wp_nonce_field('userdeck-page-add'); ?>
										<input type="hidden" name="guides_key" value="<?php echo $guides_key ?>" />
										<input class="button-primary" name="userdeck-page-add" type="submit" value="Add to Page" />
									</p>
								</div>
							</div>
						</form>
					<?php endif; ?>
				<?php endif; ?>
				
				<div class="postbox">
					<h3 class="hndle" style="cursor: auto;"><span>Copy Shortcode</h3>
					
					<div class="inside">
						<p>Copy the Guides shortcode to any of your pages or posts.</p>
						
						<?php $this->output_guides_shortcode($guides_key) ?>
					</div>
				</div>
			</div>
		</div>
	<?php else: ?>
		<p>
			An account is required to use the plugin. Don't have an account? You can create one for free.
		</p>
		
		<div id="button-connect">
			<h3>Connect to UserDeck</h3>
			<a href="javascript:void(0)" onclick="UserDeck.showConnect('login')" class="button button-primary button-hero">Login</a>
			<span style="margin: 0 10px; font-size: 16px; line-height: 42px;">or</span>
			<a href="javascript:void(0)" onclick="UserDeck.showConnect('signup')" class="button button-primary button-hero">Signup</a>
		</div>

		<div id="connect-frame"></div>

		<div id="feature-wrapper">
			<h2>Features</h2>

			<h3>Guides</h3>

			<p>
				A knowledge base widget that embeds inline to any page of your WordPress website.
			</p>

			<p>
				It inherits your theme's design and blends right in.
			</p>

			<p>
				You can embed a collection, category, or a single article instead of an entire knowledge base.
			</p>

			<p>
				Your users will save time by finding answers to common questions through self service.
			</p>

			<p>
				<a href="http://userdeck.com/guides?utm_source=wordpress&utm_medium=link&utm_campaign=website" target="_blank">Learn more about Guides</a>
			</p>
		</div>

		<script type="text/javascript">
			var plugin_settings_nonce = "<?php echo wp_create_nonce('userdeck-options'); ?>";
			var plugin_url = "<?php echo get_admin_url() . add_query_arg( array('page' => 'userdeck'), 'options-general.php' ); ?>";
		</script>
		
		<style type="text/css">
			#button-connect { margin: 40px 0; }
			#iframe-guides { display: none; box-shadow: 0 1px 1px rgba(0,0,0,.04); border: 1px solid #e5e5e5; padding: 2px; background: #fff; }
		</style>
	<?php endif; ?>
</div>
