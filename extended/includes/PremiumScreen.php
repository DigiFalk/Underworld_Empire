<?php
/**
 * The premium modules section on the Modules screen of Underworld Empire.
 *
 * @package DigiFalk\UnderworldEmpire\Extended
 */

namespace DigiFalk\UnderworldEmpire\Extended;

use DigiFalk\UnderworldEmpire\Format;
use DigiFalk\UnderworldEmpire\Icons;
use DigiFalk\UnderworldEmpire\Plugin;

defined( 'ABSPATH' ) || exit;

final class PremiumScreen {

	public static function init(): void {
		add_action( 'dfmg_admin_premium_modules', array( __CLASS__, 'render' ) );
	}

	/**
	 * Premium modules from the DigiFalk store: locked placeholders with a buy button and a
	 * license key field, or the license of an installed premium module.
	 */
	public static function render(): void {
		$catalog  = Licenses::catalog();
		$licenses = Licenses::all();
		$registry = Plugin::instance()->modules;
		$products = array_unique( array_merge( array_keys( $catalog ), array_keys( $licenses ) ) );
		?>
		<section class="dfmg-admin-premium">
			<div class="dfmg-admin-premium__head">
				<div>
					<h2 class="dfmg-admin-section-title"><?php echo Icons::svg( 'membership', 18 ); // phpcs:ignore ?> <?php esc_html_e( 'Premium modules', 'underworld-empire' ); ?></h2>
					<p><?php esc_html_e( 'Buy a module in the DigiFalk store, paste the license key from your email here and it is downloaded, installed and switched on. One payment, lifetime updates.', 'underworld-empire' ); ?></p>
				</div>
				<div class="dfmg-admin-premium__actions">
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="dfmg_license_refresh">
						<?php wp_nonce_field( 'dfmg_license_refresh' ); ?>
						<button type="submit" class="dfmg-admin-btn dfmg-admin-btn--ghost"><?php echo Icons::svg( 'activity', 15 ); // phpcs:ignore ?> <?php esc_html_e( 'Check for updates', 'underworld-empire' ); ?></button>
					</form>
					<a class="dfmg-admin-btn dfmg-admin-btn--gold" href="<?php echo esc_url( Licenses::store_url() ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Visit the store', 'underworld-empire' ); ?> <?php echo Icons::svg( 'external', 15 ); // phpcs:ignore ?></a>
				</div>
			</div>
			<?php if ( ! $products ) : ?>
				<p class="dfmg-admin-empty"><?php esc_html_e( 'No premium modules available yet. New modules appear here automatically as soon as they are in the store.', 'underworld-empire' ); ?></p>
			<?php else : ?>
				<div class="dfmg-admin-modules">
					<?php foreach ( $products as $slug ) : ?>
						<?php
						$product   = $catalog[ $slug ] ?? array( 'product' => $slug, 'name' => (string) ( $registry->info( $slug )['name'] ?? $slug ), 'description' => '', 'price' => '', 'buy_url' => Licenses::store_url(), 'icon' => '', 'requires' => '' );
						$license   = $licenses[ $slug ] ?? null;
						$info      = $registry->info( $slug );
						$licensed  = Licenses::is_licensed( $slug );
						$too_old   = $product['requires'] && version_compare( DFMG_VERSION, $product['requires'], '<' );
						$icon      = $product['icon'] && Icons::has( $product['icon'] ) ? $product['icon'] : ( Icons::has( $slug ) ? $slug : 'membership' );
						$state     = $licensed ? 'is-licensed' : ( $license ? 'is-revoked' : 'is-locked' );
						?>
						<article class="dfmg-admin-module dfmg-admin-module--premium <?php echo esc_attr( $state ); ?>" id="premium-<?php echo esc_attr( $slug ); ?>">
							<header class="dfmg-admin-module__head">
								<span class="dfmg-admin-module__icon"><?php echo Icons::svg( $icon, 22 ); // phpcs:ignore ?></span>
								<div class="dfmg-admin-module__name">
									<h2><?php echo esc_html( $product['name'] ); ?></h2>
									<span>
										<?php
										if ( $info ) {
											/* translators: %s: version */
											echo esc_html( sprintf( __( 'Installed: v%s', 'underworld-empire' ), $info['version'] ) );
										} elseif ( $product['version'] ) {
											echo esc_html( 'v' . $product['version'] );
										}
										?>
									</span>
								</div>
								<?php if ( $licensed ) : ?>
									<span class="dfmg-admin-badge dfmg-admin-badge--ok"><?php echo Icons::svg( 'check', 13 ); // phpcs:ignore ?> <?php esc_html_e( 'Licensed', 'underworld-empire' ); ?></span>
								<?php elseif ( $license ) : ?>
									<span class="dfmg-admin-badge dfmg-admin-badge--bad"><?php esc_html_e( 'License revoked', 'underworld-empire' ); ?></span>
								<?php else : ?>
									<span class="dfmg-admin-badge dfmg-admin-badge--premium"><?php echo Icons::svg( 'jail', 13 ); // phpcs:ignore ?> <?php echo esc_html( $product['price'] ?: __( 'Premium', 'underworld-empire' ) ); ?></span>
								<?php endif; ?>
							</header>
							<?php if ( $product['description'] ) : ?>
								<p class="dfmg-admin-module__desc"><?php echo esc_html( $product['description'] ); ?></p>
							<?php endif; ?>
							<?php if ( $too_old ) : ?>
								<?php /* translators: %s: version */ ?>
								<p class="dfmg-admin-note"><?php echo esc_html( sprintf( __( 'Needs Underworld Empire %s or newer. Update the plugin first.', 'underworld-empire' ), $product['requires'] ) ); ?></p>
							<?php endif; ?>

							<?php if ( $license ) : ?>
								<dl class="dfmg-admin-license">
									<div><dt><?php esc_html_e( 'Key', 'underworld-empire' ); ?></dt><dd><code><?php echo esc_html( Licenses::mask( (string) $license['key'] ) ); ?></code></dd></div>
									<div><dt><?php esc_html_e( 'License', 'underworld-empire' ); ?></dt><dd><?php echo 'lifetime' === ( $license['type'] ?? 'lifetime' ) ? esc_html__( 'Lifetime', 'underworld-empire' ) : esc_html( ucfirst( (string) $license['type'] ) ); ?></dd></div>
									<?php if ( ! empty( $license['checked_at'] ) ) : ?>
										<div><dt><?php esc_html_e( 'Checked', 'underworld-empire' ); ?></dt><dd><?php echo esc_html( Format::ago( (int) $license['checked_at'] ) ); ?></dd></div>
									<?php endif; ?>
								</dl>
								<footer class="dfmg-admin-module__foot">
									<?php if ( $licensed && ( ! $info || Licenses::update_available( $slug ) ) ) : ?>
										<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
											<input type="hidden" name="action" value="dfmg_license_update">
											<input type="hidden" name="product" value="<?php echo esc_attr( $slug ); ?>">
											<?php wp_nonce_field( 'dfmg_license_update_' . $slug ); ?>
											<button type="submit" class="dfmg-admin-btn dfmg-admin-btn--gold">
												<?php
												/* translators: %s: version */
												echo esc_html( $info ? sprintf( __( 'Update to v%s', 'underworld-empire' ), $license['latest'] ) : __( 'Download again', 'underworld-empire' ) );
												?>
											</button>
										</form>
									<?php endif; ?>
									<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('<?php echo esc_js( __( 'Deactivate the license on this site? The module is switched off and removed; its game data is kept. You can then use the key on another site.', 'underworld-empire' ) ); ?>');">
										<input type="hidden" name="action" value="dfmg_license_deactivate">
										<input type="hidden" name="product" value="<?php echo esc_attr( $slug ); ?>">
										<?php wp_nonce_field( 'dfmg_license_deactivate_' . $slug ); ?>
										<button type="submit" class="dfmg-admin-link-btn"><?php esc_html_e( 'Deactivate license', 'underworld-empire' ); ?></button>
									</form>
								</footer>
							<?php else : ?>
								<footer class="dfmg-admin-module__foot dfmg-admin-module__foot--key">
									<form class="dfmg-admin-keyform" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
										<input type="hidden" name="action" value="dfmg_license_activate">
										<input type="hidden" name="product" value="<?php echo esc_attr( $slug ); ?>">
										<?php wp_nonce_field( 'dfmg_license_activate_' . $slug ); ?>
										<label class="screen-reader-text" for="dfmg-key-<?php echo esc_attr( $slug ); ?>"><?php esc_html_e( 'License key', 'underworld-empire' ); ?></label>
										<input type="text" id="dfmg-key-<?php echo esc_attr( $slug ); ?>" name="license_key" placeholder="<?php esc_attr_e( 'Paste your license key', 'underworld-empire' ); ?>" autocomplete="off" spellcheck="false" required pattern="[A-Za-z0-9\-]{8,64}" <?php disabled( $too_old ); ?>>
										<button type="submit" class="dfmg-admin-btn dfmg-admin-btn--dark" <?php disabled( $too_old ); ?>><?php esc_html_e( 'Activate', 'underworld-empire' ); ?></button>
									</form>
									<a class="dfmg-admin-btn dfmg-admin-btn--gold" href="<?php echo esc_url( $product['buy_url'] ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Buy', 'underworld-empire' ); ?> <?php echo Icons::svg( 'external', 14 ); // phpcs:ignore ?></a>
								</footer>
							<?php endif; ?>
						</article>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</section>
		<?php
	}
}
