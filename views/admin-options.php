<div id="userdeck-wrapper" class="wrap">
	<h2>UserDeck</h2>
	
	<p><a href="http://userdeck.com?utm_source=wordpress&utm_medium=link&utm_campaign=website" target="_blank">UserDeck</a> provides customer support software that embeds into your WordPress website.</p>
	
	<?php if ($show_options): ?>
		<h2 class="nav-tab-wrapper" id="userdeck-tabs">
			<a href="<?php echo admin_url('admin.php?page=userdeck&tab=conversations') ?>" id="conversations-tab" class="nav-tab <?php if ($tab == 'conversations'): ?>nav-tab-active<?php endif; ?>">Conversations</a>
			<a href="<?php echo admin_url('admin.php?page=userdeck&tab=guides') ?>" id="guides-tab" class="nav-tab <?php if ($tab == 'guides'): ?>nav-tab-active<?php endif; ?>">Guides</a>
		</h2>
		
		<?php if ($tab == 'conversations'): ?>
			<?php if ($show_conversations_options): ?>
				<p>Conversations is ticketing system that embeds either inline on any page of your WordPress site as a contact form or an overlay widget.</p>
				<p>You can also allow your users to manage tickets from the WordPress control panel as a ticket portal.</p>
				
				<div id="poststuff">
					<div class="postbox-container" style="width:65%;">
						<form method="post" action="<?php echo admin_url('admin.php?page=userdeck') ?>">
							<div class="postbox">
								<h3 class="hndle" style="cursor: auto;"><span>Global Settings</span></h3>
								
								<div class="inside">
									<table class="form-table">
										<tbody>
											<tr valign="top">
												<th scope="row">
													Ticket Portal
												</th>
												<td>
													<input name="ticket_portal" type="checkbox" value="on" id="ticket-portal" class="checkbox double"<?php if ($ticket_portal == 1): ?> checked<?php endif; ?> />
													<label for="ticket-portal">Enable Ticket Portal</label>
													<br class="clear">
													<p class="description">Enable to allow your WordPress users to manage tickets if logged in from the control panel menu.</p>
												</td>
											</tr>
											<tr valign="top">
												<th scope="row">
													Overlay Widget
												</th>
												<td>
													<input name="overlay_widget" type="checkbox" value="on" id="overlay-widget" class="checkbox double"<?php if ($overlay_widget == 1): ?> checked<?php endif; ?> />
													<label for="overlay-widget">Enable Overlay Widget</label>
													<br class="clear">
													<p class="description">Enable to show an overlay widget which lets website visitors contact you on any page of your WordPress site and manage conversations.</p>
												</td>
											</tr>
											<tr valign="top">
												<th scope="row">
													<label for="global-mailbox-name">Mailbox</label>
												</th>
												<td>
													<select name="mailbox_id" id="global-mailbox-name">
														<option value=""></option>
														<?php foreach ($mailboxes as $mailbox): ?>
															<option value="<?php echo $mailbox['id'] ?>"<?php if ($mailbox_id == $mailbox['id']): ?> selected<?php endif; ?>><?php echo $mailbox['name'] ?></option>
														<?php endforeach; ?>
													</select>
													<br class="clear">
													<p class="description">The mailbox to use for ticket portal and overlay widgets.</p>
												</td>
											</tr>
										</tbody>
									</table>
									
									<p>
										<?php wp_nonce_field('userdeck-page-settings'); ?>
										<input class="button-primary" name="userdeck-page-settings" type="submit" value="Save Changes" />
									</p>
								</div>
							</div>
						</form>
						
						<?php if (current_user_can('publish_pages')) : ?>
							<form method="post" action="<?php echo admin_url('admin.php?page=userdeck') ?>">
								<div class="postbox">
									<h3 class="hndle" style="cursor: auto;"><span>Create a Contact Form Page</span></h3>
									
									<div class="inside">
										<p>Create a new page with the Conversations inline widget as a contact form.</p>
										
										<table class="form-table">
											<tbody>
												<tr valign="top">
													<th scope="row">
														<label for="conversations-mailbox-name-create">Mailbox</label>
													</th>
													<td>
														<select name="mailbox_id" id="conversations-mailbox-name-create">
															<option value=""></option>
															<?php foreach ($mailboxes as $mailbox): ?>
																<option value="<?php echo $mailbox['id'] ?>"><?php echo $mailbox['name'] ?></option>
															<?php endforeach; ?>
														</select>
														<br class="clear">
														<p class="description">The mailbox to use for the contact form page.</p>
													</td>
												</tr>
												<tr valign="top">
													<th scope="row">
														<label for="conversations-page-title">Page Title</label>
													</th>
													<td>
														<input name="page_title" type="text" value="" placeholder="Contact" id="conversations-page-title" />
														<br class="clear">
														<p class="description">The title of the new contact form page to create.</p>
													</td>
												</tr>
											</tbody>
										</table>
										
										<p>
											<?php wp_nonce_field('userdeck-page-conversations-create'); ?>
											<input type="hidden" name="account_key" value="<?php echo $account_key ?>" />
											<input class="button-primary" name="userdeck-page-conversations-create" type="submit" value="Create Page" />
										</p>
									</div>
								</div>
							</form>
						<?php endif; ?>
						
						<?php if (current_user_can('edit_pages')) : ?>
							<?php if (count($pages) > 0): ?>
								<form method="post" action="<?php echo admin_url('admin.php?page=userdeck') ?>">
									<div class="postbox">
										<h3 class="hndle" style="cursor: auto;"><span>Add Contact Form to Page</span></h3>
										
										<div class="inside">
											<p>Add the Conversations inline widget as a contact form to an existing page.</p>
											
											<table class="form-table">
												<tbody>
													<tr valign="top">
														<th scope="row">
															<label for="conversations-mailbox-name-add">Mailbox</label>
														</th>
														<td>
															<select name="mailbox_id" id="conversations-mailbox-name-add">
																<option value=""></option>
																<?php foreach ($mailboxes as $mailbox): ?>
																	<option value="<?php echo $mailbox['id'] ?>"><?php echo $mailbox['name'] ?></option>
																<?php endforeach; ?>
															</select>
															<br class="clear">
															<p class="description">The mailbox to use for the contact form page.</p>
														</td>
													</tr>
													<tr valign="top">
														<th scope="row">
															<label for="conversations-page-id">Page</label>
														</th>
														<td>
															<select name="page_id" id="conversations-page-id">
																<?php foreach ($pages as $id => $title): ?>
																	<option value="<?php echo $id ?>"><?php echo $title ?></option>
																<?php endforeach; ?>
															</select>
															<br class="clear">
															<p class="description">The title of the existing page to update with a contact form.</p>
														</td>
													</tr>
												</tbody>
											</table>
											
											<p>
												<?php wp_nonce_field('userdeck-page-conversations-add'); ?>
												<input type="hidden" name="account_key" value="<?php echo $account_key ?>" />
												<input class="button-primary" name="userdeck-page-conversations-add" type="submit" value="Add to Page" />
											</p>
										</div>
									</div>
								</form>
							<?php endif; ?>
						<?php endif; ?>
					</div>
				</div>
			<?php else: ?>
				<div id="button-connect">
					<h3>Enable Conversations</h3>
					<p>Your account does not have the Conversations enabled. You can enable it below to start a free trial.</p>
					<a href="javascript:void(0)" onclick="UserDeck.showConnect('login', 'conversations')" class="button button-primary button-hero">Enable Conversations</a>
				</div>

				<div id="connect-frame"></div>
		
				<div id="feature-wrapper">
					<h2>Converations Features</h2>

					<ul>
						<li>
							A ticketing system to allow your customers to contact you through email and embedded widgets.
						</li>
						<li>
							Inline widget can be used as a contact form or a ticket portal to let users manage tickets from inside the WordPress control panel.
						</li>
						<li>
							Integrates with WordPress for authenticated sessions to track user name and email on tickets.
						</li>
					</ul>

					<p>
						<a href="http://userdeck.com/conversations?utm_source=wordpress&utm_medium=link&utm_campaign=website" target="_blank">Learn more about Conversations</a>
					</p>
				</div>

				<script type="text/javascript">
					var plugin_settings_nonce = "<?php echo wp_create_nonce('userdeck-options'); ?>";
					var plugin_url = "<?php echo get_admin_url() . add_query_arg( array('page' => 'userdeck'), 'admin.php' ); ?>";
				</script>
				
				<style type="text/css">
					#button-connect { margin: 40px 0; }
					#iframe-guides { display: none; box-shadow: 0 1px 1px rgba(0,0,0,.04); border: 1px solid #e5e5e5; padding: 2px; background: #fff; }
					#feature-wrapper ul { list-style-type: disc; padding-left: 20px; }
				</style>
			<?php endif; ?>
		<?php elseif ($tab == 'guides'): ?>
			<p>Guides is a knowledge base widget that embeds inline into any page of your WordPress pages and inherits the styling and blends in.</p>
			
			<?php if ($show_guides_options): ?>
				<div id="poststuff">
					<div class="postbox-container" style="width:65%;">
						<?php if (current_user_can('publish_pages')) : ?>
							<form method="post" action="<?php echo admin_url('admin.php?page=userdeck') ?>">
								<div class="postbox">
									<h3 class="hndle" style="cursor: auto;"><span>Create a Knowledge Base Page</span></h3>
									
									<div class="inside">
										<p>Create a new page with the Guides knowledge base inline widget.</p>
										
										<table class="form-table">
											<tbody>
												<tr valign="top">
													<th scope="row">
														<label for="guides-name-create">Guide</label>
													</th>
													<td>
														<select name="guides_key" id="guides-name-create">
															<?php foreach ($guides as $guide): ?>
																<option value="<?php echo $guide['key'] ?>"><?php echo $guide['name'] ?></option>
															<?php endforeach; ?>
														</select>
														<br class="clear">
														<p class="description">The guide to use for the knowledge base page.</p>
													</td>
												</tr>
												<tr valign="top">
													<th scope="row">
														<label for="guides-page-title">Page Title</label>
													</th>
													<td>
														<input name="page_title" type="text" value="" placeholder="Support" id="guides-page-title" />
														<br class="clear">
														<p class="description">The title of the new knowledge base page to create.</p>
													</td>
												</tr>
											</tbody>
										</table>
										
										<p>
											<?php wp_nonce_field('userdeck-page-guides-create'); ?>
											<input class="button-primary" name="userdeck-page-guides-create" type="submit" value="Create Page" />
										</p>
									</div>
								</div>
							</form>
						<?php endif; ?>
						
						<?php if (current_user_can('edit_pages')) : ?>
							<?php if (count($pages) > 0): ?>
								<form method="post" action="<?php echo admin_url('admin.php?page=userdeck') ?>">
									<div class="postbox">
										<h3 class="hndle" style="cursor: auto;"><span>Add Knowledge Base to Page</span></h3>
										
										<div class="inside">
											<p>Add the Guides knowledge base inline widget to an existing page.</p>
											
											<table class="form-table">
												<tbody>
													<tr valign="top">
														<th scope="row">
															<label for="guides-name-add">Guide</label>
														</th>
														<td>
															<select name="guides_key" id="guides-name-add">
																<?php foreach ($guides as $guide): ?>
																	<option value="<?php echo $guide['key'] ?>"><?php echo $guide['name'] ?></option>
																<?php endforeach; ?>
															</select>
															<br class="clear">
															<p class="description">The guide to use for the knowledge base page.</p>
														</td>
													</tr>
													<tr valign="top">
														<th scope="row">
															<label for="guides-page-id">Page</label>
														</th>
														<td>
															<select name="page_id" id="guides-page-id">
																<?php foreach ($pages as $id => $title): ?>
																	<option value="<?php echo $id ?>"><?php echo $title ?></option>
																<?php endforeach; ?>
															</select>
															<br class="clear">
															<p class="description">The title of the existing page to update with a knowledge base.</p>
														</td>
													</tr>
												</tbody>
											</table>
											
											<p>
												<?php wp_nonce_field('userdeck-page-guides-add'); ?>
												<input class="button-primary" name="userdeck-page-guides-add" type="submit" value="Add to Page" />
											</p>
										</div>
									</div>
								</form>
							<?php endif; ?>
						<?php endif; ?>
					</div>
				</div>
			<?php else: ?>
				<div id="button-connect">
					<h3>Enable Guides</h3>
					<p>Your account does not have the Guides enabled. You can enable it below to start a free trial.</p>
					<a href="javascript:void(0)" onclick="UserDeck.showConnect('login', 'guides')" class="button button-primary button-hero">Enable Guides</a>
				</div>

				<div id="connect-frame"></div>
		
				<div id="feature-wrapper">
					<h2>Converations Features</h2>

					<ul>
						<li>
							A ticketing system to allow your customers to contact you through email and embedded widgets.
						</li>
						<li>
							Inline widget can be used as a contact form or a ticket portal to let users manage tickets from inside the WordPress control panel.
						</li>
						<li>
							Integrates with WordPress for authenticated sessions to track user name and email on tickets.
						</li>
					</ul>

					<p>
						<a href="http://userdeck.com/guides?utm_source=wordpress&utm_medium=link&utm_campaign=website" target="_blank">Learn more about Guides</a>
					</p>
				</div>

				<script type="text/javascript">
					var plugin_settings_nonce = "<?php echo wp_create_nonce('userdeck-options'); ?>";
					var plugin_url = "<?php echo get_admin_url() . add_query_arg( array('page' => 'userdeck'), 'admin.php' ); ?>";
				</script>
				
				<style type="text/css">
					#button-connect { margin: 40px 0; }
					#iframe-guides { display: none; box-shadow: 0 1px 1px rgba(0,0,0,.04); border: 1px solid #e5e5e5; padding: 2px; background: #fff; }
					#feature-wrapper ul { list-style-type: disc; padding-left: 20px; }
				</style>
			<?php endif; ?>
		<?php endif; ?>
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
			
			<h3>Converations</h3>

			<ul>
				<li>
					A ticketing system to allow your customers to contact you through email and embedded widgets.
				</li>
				<li>
					Inline widget can be used as a contact form or a ticket portal to let users manage tickets from inside the WordPress control panel.
				</li>
				<li>
					Integrates with WordPress for authenticated sessions to track user name and email on tickets.
				</li>
			</ul>

			<p>
				<a href="http://userdeck.com/conversations?utm_source=wordpress&utm_medium=link&utm_campaign=website" target="_blank">Learn more about Conversations</a>
			</p>
			
			<br>

			<h3>Guides</h3>

			<ul>
				<li>
					A knowledge base widget that embeds inline to any page of your WordPress website.
				</li>
				<li>
					It inherits your theme's design and blends right in.
				</li>
				<li>
					You can embed a collection, category, or a single article instead of an entire knowledge base.
				</li>
				<li>
					Your users will save time by finding answers to common questions through self service.
				</li>
			</ul>

			<p>
				<a href="http://userdeck.com/guides?utm_source=wordpress&utm_medium=link&utm_campaign=website" target="_blank">Learn more about Guides</a>
			</p>
		</div>

		<script type="text/javascript">
			var plugin_settings_nonce = "<?php echo wp_create_nonce('userdeck-options'); ?>";
			var plugin_url = "<?php echo get_admin_url() . add_query_arg( array('page' => 'userdeck'), 'admin.php' ); ?>";
		</script>
		
		<style type="text/css">
			#button-connect { margin: 40px 0; }
			#iframe-guides { display: none; box-shadow: 0 1px 1px rgba(0,0,0,.04); border: 1px solid #e5e5e5; padding: 2px; background: #fff; }
			#feature-wrapper ul { list-style-type: disc; padding-left: 20px; }
		</style>
	<?php endif; ?>
</div>
