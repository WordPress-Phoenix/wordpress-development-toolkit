<script id="tmpl-wp-phx-tools" type="text/html">
	<table width="100%">
		<tr>
			<td width="100px">
				<a href="<?php echo admin_url( 'admin.php?page=wp-phx-generator&generator=plugin&modal_view=true&TB_iframe=true&height=680&width=800'); ?>"
				   title="Start New Plugin" class="thickbox">
					<span class="dashicons dashicons-admin-plugins"></span> Start New Plugin</a>
			</td>
		</tr>
	</table>
</script>
<script id="tmpl-wp-phx-standards-best-practices" type="text/html">
	<small><strong>These guides are fresh and incomplete</strong> and not the best they can be yet! Help contribute!</small>
	<table width="100%">
		<tr>
			<td width="100px">
				<span class="dashicons dashicons-media-code"></span>
				<a href="https://github.com/WordPress-Phoenix/wordpress-development-toolkit/blob/master/docs/best
				-practices-asset-management.md" target="_blank">Asset Management</a>
			</td>
		</tr>
		<tr>
			<td width="100px">
				<span class="dashicons dashicons-networking"></span>
				<a href="https://github.com/WordPress-Phoenix/wordpress-development-toolkit/blob/master/docs/best
				-practices-asset-management.md" target="_blank">Action & Filter Hooks</a>
			</td>
		</tr>
		<tr>
			<td width="100px">
				<span class="dashicons dashicons-hammer"></span>
				<a href="https://github.com/WordPress-Phoenix/wordpress-development-toolkit/blob/master/docs/code.md" target="_blank">
					PHP, CSS & JavaScript Coding Standards
				</a>
			</td>
		</tr>
		<tr>
			<td width="100px">
				<span class="dashicons dashicons-lock"></span>
				<a href="https://github.com/WordPress-Phoenix/wordpress-development-toolkit/blob/master/docs/security.md" target="_blank">
					Security
				</a>
			</td>
		</tr>
	</table>
</script>
<script id="tmpl-wp-phx-guides-glossaries" type="text/html">
	<small><strong>These guides are fresh and incomplete</strong> and not the best they can be yet! Help contribute!</small>
	<table width="100%">
		<tr>
			<td width="100px">
				<span class="dashicons dashicons-media-code"></span>
				<a href="https://github.com/WordPress-Phoenix/wordpress-development-toolkit/blob/master/docs
				/resources.md" target="_blank">Resources</a>
			</td>
		</tr>
	</table>
</script>
<script id="tmpl-wp-phx-help-resources" type="text/html">
	<div
		style="display: block; width:40px;height:40px;background:url( <?php echo
			$this->installed_url . 'app/assets/slack.svg'; ?>);background-size:cover;">

	</div>
	<h4><code><a href="slack://channel?id=C03S74AUT&team=T029A195J">#wordpress</a></code> channel
		in the Time Inc Slack</h4>
</script>
<script id="tmpl-wp-phx-dev-kit-header" type="text/html">
	<section class="wp-phx-header" style="background:#999;height:120px;padding:1.33rem 3rem 1rem 3rem;">
		<span class="dashicons dashicons-admin-tools" style="font-size: 128px;color: #333;float: right;margin-right:
		5rem;margin-top: -0.25rem;"></span>
		<h1>
			<div style="margin-top:1rem;color:#383838;">
				<code style="font-weight:700;font-size:1.25rem;">
					WordPress Development Toolkit
				</code>
			</div>
		</h1>
	</section>
	<section style="background:#131313;height:1rem;padding:1.33rem 3rem 1rem 3rem;">
		<code style="color:#eee;font-weight:700;font-size:0.9rem;">
			Tools, References and Resources
		</code>
	</section>
	<br class="clear" />
</script>
<script id="tmpl-wp-phx-dev-kit-main" type="text/html">
	<div id="plugin-filter" method="post">
		<div class="wp-list-table widefat plugin-install" >
			<div id="the-list">
				<# _.each( data.cards, function ( card ) { #>
					<div class="plugin-card plugin-card-{{{ card.slug }}}" style="min-height:15rem !important;">
						<div class="plugin-card-top">
							<div class="name column-name">
								<h3>{{{ card.title }}}
									<span class="dashicons dashicons-{{{ card.dashicon }}} plugin-icon"
										  style="font-size:128px;color:#666;"></span>
								</h3>
							</div>
							<div class="desc column-description">
								<p>{{{ card.description }}}</p>
								<# if ( card.contentTmpl ) {
									var cardContent = wp.template( card.contentTmpl );
									print( cardContent( card ) );
								} #>
							</div>
						</div>
					</div>
				<# } ); #>
			</div>
		</div>
	</div>
	<br class="clear" />
</script>