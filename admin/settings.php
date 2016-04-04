<?php
/**
 * Provide a dashboard view for the plugin
 *
 * This file is used to markup the plugin settings and faq pages
 *
 * @since      1.0.0
 *
 * @package    Mesh
 * @subpackage Mesh/admin
 */

/**
 * Options Page
 *
 * Renders the settings page contents.
 *
 * @since       1.0.0
*/

require_once LINCHPIN_MCS___PLUGIN_DIR . '/lib/Michelf/MarkdownInterface.php';
require_once LINCHPIN_MCS___PLUGIN_DIR . '/lib/Michelf/Markdown.php';
require_once LINCHPIN_MCS___PLUGIN_DIR . '/lib/Michelf/MarkdownExtra.php';

use \Michelf\MarkdownExtra;

?>
<div class="wrap">
	<h2><?php esc_html_e( get_admin_page_title() ); ?> </h2>
	<?php settings_errors( self::$plugin_name . '-notices' ); ?>
	<h2 class="nav-tab-wrapper">
		<?php
		foreach ( $tabs as $tab_slug => $tab_name ) :

			$tab_url = add_query_arg( array(
				'settings-updated' => false,
				'tab' => $tab_slug,
			) );

			$active = ( $active_tab === $tab_slug ) ? ' nav-tab-active' : '';

			?>
			<a href="<?php echo esc_url( $tab_url ); ?>" title="<?php esc_attr_e( $tab_name ); ?>" class="nav-tab <?php esc_attr_e( $active ); ?>">
				<?php esc_html_e( $tab_name ); ?>
			</a>
		<?php endforeach; ?>
	</h2>

	<div id="poststuff">
		<div id="post-body" class="metabox-holder">
			<div id="postbox-container" class="postbox-container">
				<?php if ( 'settings' === $active_tab ) : ?>
					<?php include_once( LINCHPIN_MCS___PLUGIN_DIR . '/admin/settings-meta-box-display.php' ); ?>
				<?php elseif ( 'changelog' === $active_tab ) : ?>
					<?php
						$readme = file_get_contents( LINCHPIN_MCS___PLUGIN_DIR . '/CHANGELOG.md', true );
						echo MarkdownExtra::defaultTransform( $readme ); // WPCS: ok.
					?>
				<?php elseif ( 'faq' === $active_tab ) : ?>
					<?php
						$readme = file_get_contents( LINCHPIN_MCS___PLUGIN_DIR . '/README.md', true );
						echo MarkdownExtra::defaultTransform( $readme ); // WPCS: ok.
					?>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>
