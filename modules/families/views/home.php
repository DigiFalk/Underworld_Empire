<?php
/**
 * @var \DigiFalk\MafiaGame\Character $c
 * @var array                          $family
 * @var array                          $members
 * @var int                            $cap
 * @var array                          $invites
 * @var array                          $log
 * @var array                          $permissions
 * @var bool                           $is_boss
 * @var int                            $upgrade
 * @var int                            $cash_tax
 * @var int                            $bullet_tax
 *
 * @package DigiFalk\MafiaGame
 */

use DigiFalk\MafiaGame\Character;
use DigiFalk\MafiaGame\Format;
use DigiFalk\MafiaGame\Locations;
use DigiFalk\MafiaGame\Modules\Families;

defined( 'ABSPATH' ) || exit;
?>
<div class="dfmg-grid dfmg-grid--2">
	<section class="dfmg-card">
		<h3><?php echo esc_html( $family['name'] ); ?></h3>
		<table class="dfmg-table dfmg-table--keyvalue">
			<tr><th><?php esc_html_e( 'Your role', 'wp-mafia-game' ); ?></th><td><?php echo esc_html( Families::role( $family, $c->id() ) ); ?></td></tr>
			<tr><th><?php esc_html_e( 'City', 'wp-mafia-game' ); ?></th><td><?php echo esc_html( Locations::name( (int) $family['location_id'] ) ); ?></td></tr>
			<tr><th><?php esc_html_e( 'Members', 'wp-mafia-game' ); ?></th><td><?php echo (int) count( $members ) . ' / ' . (int) $cap; ?></td></tr>
			<tr><th><?php esc_html_e( 'Vault', 'wp-mafia-game' ); ?></th><td><?php echo esc_html( Format::money( $family['money'] ) ); ?></td></tr>
			<tr><th><?php esc_html_e( 'Bullets', 'wp-mafia-game' ); ?></th><td><?php echo esc_html( Format::number( $family['bullets'] ) ); ?></td></tr>
		</table>
		<?php if ( $family['internal'] ) : ?>
			<h4><?php esc_html_e( 'Notice', 'wp-mafia-game' ); ?></h4>
			<div class="dfmg-usertext"><?php echo Format::user_text( (string) $family['internal'] ); // phpcs:ignore ?></div>
		<?php endif; ?>
		<?php if ( Families::can( $c, 'upgrade' ) ) : ?>
			<?php echo $this->button( 'upgrade', sprintf( /* translators: %s: money */ __( 'Buy an extra slot (%s from the vault)', 'wp-mafia-game' ), Format::money( $upgrade ) ) ); // phpcs:ignore ?>
		<?php endif; ?>
	</section>

	<section class="dfmg-card">
		<h3><?php esc_html_e( 'Family vault', 'wp-mafia-game' ); ?></h3>
		<p class="dfmg-muted"><?php echo esc_html( sprintf( /* translators: 1: percent, 2: percent */ __( 'Depositing loses %1$d%% of the money and %2$d%% of the bullets.', 'wp-mafia-game' ), $cash_tax, $bullet_tax ) ); ?></p>
		<?php echo $this->form( 'deposit' ); // phpcs:ignore ?>
			<input type="text" inputmode="numeric" name="amount" placeholder="<?php esc_attr_e( 'Amount', 'wp-mafia-game' ); ?>" required>
			<select name="what"><option value="money"><?php esc_html_e( 'Money', 'wp-mafia-game' ); ?></option><option value="bullets"><?php esc_html_e( 'Bullets', 'wp-mafia-game' ); ?></option></select>
			<button type="submit" class="dfmg-button"><?php esc_html_e( 'Deposit', 'wp-mafia-game' ); ?></button>
		</form>
		<?php if ( Families::can( $c, 'withdraw_money' ) || Families::can( $c, 'withdraw_bullets' ) ) : ?>
			<?php echo $this->form( 'withdraw' ); // phpcs:ignore ?>
				<input type="text" inputmode="numeric" name="amount" placeholder="<?php esc_attr_e( 'Amount', 'wp-mafia-game' ); ?>" required>
				<select name="what">
					<?php if ( Families::can( $c, 'withdraw_money' ) ) : ?><option value="money"><?php esc_html_e( 'Money', 'wp-mafia-game' ); ?></option><?php endif; ?>
					<?php if ( Families::can( $c, 'withdraw_bullets' ) ) : ?><option value="bullets"><?php esc_html_e( 'Bullets', 'wp-mafia-game' ); ?></option><?php endif; ?>
				</select>
				<button type="submit" class="dfmg-button dfmg-button--ghost"><?php esc_html_e( 'Withdraw', 'wp-mafia-game' ); ?></button>
			</form>
		<?php endif; ?>
	</section>
</div>

<h3><?php esc_html_e( 'Members', 'wp-mafia-game' ); ?></h3>
<table class="dfmg-table">
	<thead>
		<tr>
			<th><?php esc_html_e( 'Name', 'wp-mafia-game' ); ?></th>
			<th><?php esc_html_e( 'Role', 'wp-mafia-game' ); ?></th>
			<th><?php esc_html_e( 'Rank', 'wp-mafia-game' ); ?></th>
			<th><?php esc_html_e( 'Status', 'wp-mafia-game' ); ?></th>
			<th></th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ( $members as $dfmg_m ) : ?>
			<?php $dfmg_mc = $dfmg_m['character']; ?>
			<tr>
				<td><?php echo $dfmg_mc->link(); // phpcs:ignore ?></td>
				<td><?php echo esc_html( Families::role( $family, $dfmg_mc->id() ) ); ?></td>
				<td><?php echo esc_html( $dfmg_mc->rank_name() ); ?></td>
				<td><?php echo $dfmg_mc->is_online() ? '<span class="dfmg-online">' . esc_html__( 'online', 'wp-mafia-game' ) . '</span>' : '<span class="dfmg-muted">' . esc_html__( 'offline', 'wp-mafia-game' ) . '</span>'; ?></td>
				<td class="dfmg-actions">
					<?php if ( $is_boss && $dfmg_mc->id() !== $c->id() ) : ?>
						<details class="dfmg-details">
							<summary><?php esc_html_e( 'Permissions', 'wp-mafia-game' ); ?></summary>
							<?php echo $this->form( 'roles', array( 'member' => $dfmg_mc->id() ) ); // phpcs:ignore ?>
								<select name="role">
									<option value=""><?php esc_html_e( 'Role unchanged', 'wp-mafia-game' ); ?></option>
									<option value="member"><?php esc_html_e( 'Member', 'wp-mafia-game' ); ?></option>
									<option value="underboss"><?php esc_html_e( 'Underboss', 'wp-mafia-game' ); ?></option>
									<option value="boss"><?php esc_html_e( 'Boss (hand over leadership)', 'wp-mafia-game' ); ?></option>
								</select>
								<?php $dfmg_has = array_filter( explode( ',', (string) $dfmg_m['permissions'] ) ); ?>
								<?php foreach ( $permissions as $dfmg_key => $dfmg_label ) : ?>
									<label class="dfmg-check"><input type="checkbox" name="perms[]" value="<?php echo esc_attr( $dfmg_key ); ?>" <?php checked( in_array( $dfmg_key, $dfmg_has, true ) ); ?>> <?php echo esc_html( $dfmg_label ); ?></label>
								<?php endforeach; ?>
								<button type="submit" class="dfmg-button dfmg-button--small"><?php esc_html_e( 'Save', 'wp-mafia-game' ); ?></button>
							</form>
						</details>
					<?php endif; ?>
					<?php if ( Families::can( $c, 'kick' ) && $dfmg_mc->id() !== $c->id() && $dfmg_mc->id() !== (int) $family['boss_id'] ) : ?>
						<?php echo $this->button( 'kick', __( 'Kick', 'wp-mafia-game' ), array( 'member' => $dfmg_mc->id() ), 'dfmg-button dfmg-button--danger dfmg-button--small' ); // phpcs:ignore ?>
					<?php endif; ?>
				</td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>

<div class="dfmg-grid dfmg-grid--2">
	<?php if ( Families::can( $c, 'invite' ) ) : ?>
		<section class="dfmg-card">
			<h3><?php esc_html_e( 'Invite', 'wp-mafia-game' ); ?></h3>
			<?php echo $this->form( 'invite' ); // phpcs:ignore ?>
				<input type="text" name="name" placeholder="<?php esc_attr_e( 'Player name', 'wp-mafia-game' ); ?>" required>
				<button type="submit" class="dfmg-button"><?php esc_html_e( 'Invite', 'wp-mafia-game' ); ?></button>
			</form>
			<?php if ( $invites ) : ?>
				<ul class="dfmg-list">
					<?php foreach ( $invites as $dfmg_inv ) : ?>
						<li><?php echo Character::link_by_id( (int) $dfmg_inv['character_id'] ); // phpcs:ignore ?> <?php echo $this->button( 'cancel_invite', __( 'Withdraw', 'wp-mafia-game' ), array( 'invite' => $dfmg_inv['id'] ), 'dfmg-button dfmg-button--ghost dfmg-button--small' ); // phpcs:ignore ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</section>
	<?php endif; ?>

	<?php if ( Families::can( $c, 'edit_profile' ) || Families::can( $c, 'edit_internal' ) ) : ?>
		<section class="dfmg-card">
			<h3><?php esc_html_e( 'Texts', 'wp-mafia-game' ); ?></h3>
			<?php if ( Families::can( $c, 'edit_profile' ) ) : ?>
				<?php echo $this->form( 'edit', array( 'field' => 'description' ) ); // phpcs:ignore ?>
					<label><?php esc_html_e( 'Public profile', 'wp-mafia-game' ); ?><textarea name="text" rows="4"><?php echo esc_textarea( (string) $family['description'] ); ?></textarea></label>
					<button type="submit" class="dfmg-button dfmg-button--small"><?php esc_html_e( 'Save', 'wp-mafia-game' ); ?></button>
				</form>
			<?php endif; ?>
			<?php if ( Families::can( $c, 'edit_internal' ) ) : ?>
				<?php echo $this->form( 'edit', array( 'field' => 'internal' ) ); // phpcs:ignore ?>
					<label><?php esc_html_e( 'Internal notice', 'wp-mafia-game' ); ?><textarea name="text" rows="4"><?php echo esc_textarea( (string) $family['internal'] ); ?></textarea></label>
					<button type="submit" class="dfmg-button dfmg-button--small"><?php esc_html_e( 'Save', 'wp-mafia-game' ); ?></button>
				</form>
			<?php endif; ?>
		</section>
	<?php endif; ?>
</div>

<?php if ( $log ) : ?>
	<h3><?php esc_html_e( 'Log', 'wp-mafia-game' ); ?></h3>
	<ul class="dfmg-list dfmg-log">
		<?php foreach ( $log as $dfmg_l ) : ?>
			<li><?php echo esc_html( $dfmg_l['message'] ); ?> <small><?php echo esc_html( Format::ago( (int) $dfmg_l['created_at'] ) ); ?></small></li>
		<?php endforeach; ?>
	</ul>
<?php endif; ?>

<div class="dfmg-card dfmg-card--danger">
	<?php if ( $is_boss ) : ?>
		<?php echo $this->form( 'disband' ); // phpcs:ignore ?>
			<label><input type="checkbox" name="confirm" value="1" required> <?php esc_html_e( 'I want to permanently disband the family', 'wp-mafia-game' ); ?></label>
			<button type="submit" class="dfmg-button dfmg-button--danger"><?php esc_html_e( 'Disband family', 'wp-mafia-game' ); ?></button>
		</form>
	<?php else : ?>
		<?php echo $this->button( 'leave', __( 'Leave family', 'wp-mafia-game' ), array(), 'dfmg-button dfmg-button--danger' ); // phpcs:ignore ?>
	<?php endif; ?>
</div>
