<?php
/**
 * Player avatars: uploaded on the game profile, stored as .webp and used as the
 * WordPress avatar of the user everywhere on the site (comments, admin, author boxes).
 *
 * @package DigiFalk\UnderworldEmpire
 */

namespace DigiFalk\UnderworldEmpire;

defined( 'ABSPATH' ) || exit;

final class Avatar {

	const META = 'dfmg_avatar';
	const DIR  = 'underworld-avatars';

	public static function init(): void {
		add_filter( 'pre_get_avatar_data', array( __CLASS__, 'avatar_data' ), 10, 2 );
		add_filter( 'user_profile_picture_description', array( __CLASS__, 'profile_description' ), 10, 2 );
		add_action( 'delete_user', array( __CLASS__, 'delete' ) );
	}

	/**
	 * Can this server write .webp images (GD or Imagick with WebP support)?
	 */
	public static function supported(): bool {
		return wp_image_editor_supports( array( 'mime_type' => 'image/webp' ) );
	}

	private static function dir(): array {
		$uploads = wp_upload_dir( null, false );
		return array(
			'path' => trailingslashit( $uploads['basedir'] ) . self::DIR,
			'url'  => trailingslashit( $uploads['baseurl'] ) . self::DIR,
		);
	}

	/**
	 * URL of the uploaded avatar of a user ('' when none).
	 */
	public static function url( int $user_id ): string {
		$file = (string) get_user_meta( $user_id, self::META, true );
		if ( '' === $file ) {
			return '';
		}
		$dir = self::dir();
		return is_file( $dir['path'] . '/' . $file ) ? $dir['url'] . '/' . rawurlencode( $file ) : '';
	}

	/**
	 * The picture to show for a player everywhere in the game: the uploaded avatar, otherwise
	 * the normal WordPress avatar (Gravatar or the default picture chosen under Settings →
	 * Discussion), exactly like on the profile page. '' when avatars are switched off in
	 * WordPress, so the caller can show the first letter of the name instead.
	 */
	public static function display_url( int $user_id, int $size = 64 ): string {
		$own = self::url( $user_id );
		if ( $own ) {
			return $own;
		}
		if ( ! $user_id || ! get_option( 'show_avatars' ) ) {
			return '';
		}
		return (string) get_avatar_url( $user_id, array( 'size' => max( 32, $size * 2 ) ) );
	}

	/**
	 * User id for anything get_avatar() accepts.
	 *
	 * @param mixed $id_or_email
	 */
	private static function user_id( $id_or_email ): int {
		if ( is_numeric( $id_or_email ) ) {
			return (int) $id_or_email;
		}
		if ( $id_or_email instanceof \WP_User ) {
			return (int) $id_or_email->ID;
		}
		if ( $id_or_email instanceof \WP_Post ) {
			return (int) $id_or_email->post_author;
		}
		if ( $id_or_email instanceof \WP_Comment ) {
			return (int) $id_or_email->user_id;
		}
		if ( is_string( $id_or_email ) && is_email( $id_or_email ) ) {
			$user = get_user_by( 'email', $id_or_email );
			return $user ? (int) $user->ID : 0;
		}
		return 0;
	}

	/**
	 * Use the uploaded avatar instead of Gravatar.
	 */
	public static function avatar_data( array $args, $id_or_email ): array {
		$user_id = self::user_id( $id_or_email );
		$url     = $user_id ? self::url( $user_id ) : '';
		if ( $url ) {
			$args['url']          = $url;
			$args['found_avatar'] = true;
		}
		return $args;
	}

	public static function profile_description( string $description, $user ): string {
		if ( $user instanceof \WP_User && self::url( (int) $user->ID ) ) {
			return __( 'This picture was uploaded on the game profile page and can be changed there.', 'underworld-empire' );
		}
		return $description;
	}

	/**
	 * Store an uploaded image ($_FILES entry) as the avatar of a user.
	 *
	 * @return true|\WP_Error
	 */
	public static function save( int $user_id, array $file, int $size = 256, int $max_kb = 2048 ) {
		$error = (int) ( $file['error'] ?? UPLOAD_ERR_NO_FILE );
		if ( UPLOAD_ERR_NO_FILE === $error || empty( $file['tmp_name'] ) ) {
			return new \WP_Error( 'dfmg_avatar', __( 'Choose an image first.', 'underworld-empire' ) );
		}
		if ( UPLOAD_ERR_INI_SIZE === $error || UPLOAD_ERR_FORM_SIZE === $error || (int) $file['size'] > $max_kb * 1024 ) {
			/* translators: %s: maximum file size */
			return new \WP_Error( 'dfmg_avatar', sprintf( __( 'The image is too large. The maximum is %s.', 'underworld-empire' ), size_format( $max_kb * 1024 ) ) );
		}
		if ( UPLOAD_ERR_OK !== $error || ! is_uploaded_file( $file['tmp_name'] ) ) {
			return new \WP_Error( 'dfmg_avatar', __( 'The upload failed, please try again.', 'underworld-empire' ) );
		}
		$mimes = array(
			'jpg|jpeg|jpe' => 'image/jpeg',
			'png'          => 'image/png',
			'gif'          => 'image/gif',
			'webp'         => 'image/webp',
		);
		$check = wp_check_filetype_and_ext( $file['tmp_name'], (string) $file['name'], $mimes );
		$info  = @getimagesize( $file['tmp_name'] ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		if ( empty( $check['type'] ) || ! $info || ! in_array( $info['mime'] ?? '', $mimes, true ) ) {
			return new \WP_Error( 'dfmg_avatar', __( 'Upload a JPG, PNG, GIF or WebP image.', 'underworld-empire' ) );
		}
		if ( ! self::supported() ) {
			return new \WP_Error( 'dfmg_avatar', __( 'This server can\'t create WebP images. Ask the site administrator to enable WebP support in GD or Imagick.', 'underworld-empire' ) );
		}

		$editor = wp_get_image_editor( $file['tmp_name'] );
		if ( is_wp_error( $editor ) ) {
			return new \WP_Error( 'dfmg_avatar', __( 'This image can\'t be read.', 'underworld-empire' ) );
		}
		// Square crop from the centre, scaled down to the avatar size.
		$dims   = $editor->get_size();
		$side   = min( (int) $dims['width'], (int) $dims['height'] );
		$target = min( $side, max( 32, $size ) );
		$result = $editor->crop( (int) floor( ( $dims['width'] - $side ) / 2 ), (int) floor( ( $dims['height'] - $side ) / 2 ), $side, $side, $target, $target );
		if ( is_wp_error( $result ) ) {
			return new \WP_Error( 'dfmg_avatar', __( 'This image can\'t be processed.', 'underworld-empire' ) );
		}
		$editor->set_quality( 82 );

		$dir = self::dir();
		if ( ! wp_mkdir_p( $dir['path'] ) ) {
			return new \WP_Error( 'dfmg_avatar', __( 'The avatar folder can\'t be created.', 'underworld-empire' ) );
		}
		if ( ! is_file( $dir['path'] . '/index.php' ) ) {
			file_put_contents( $dir['path'] . '/index.php', "<?php\n// Silence is golden.\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		}
		// A new random name for every upload, so browsers and caches show the new picture.
		$name  = $user_id . '-' . strtolower( wp_generate_password( 10, false ) ) . '.webp';
		$saved = $editor->save( $dir['path'] . '/' . $name, 'image/webp' );
		if ( is_wp_error( $saved ) || 'image/webp' !== ( $saved['mime-type'] ?? '' ) ) {
			return new \WP_Error( 'dfmg_avatar', __( 'The image could not be saved.', 'underworld-empire' ) );
		}
		if ( basename( (string) $saved['path'] ) !== $name ) {
			$name = basename( (string) $saved['path'] );
		}

		self::delete_file( $user_id );
		update_user_meta( $user_id, self::META, $name );
		do_action( 'dfmg_avatar_updated', $user_id, self::url( $user_id ) );
		return true;
	}

	private static function delete_file( int $user_id ): void {
		$file = (string) get_user_meta( $user_id, self::META, true );
		if ( '' !== $file && 0 === validate_file( $file ) ) {
			$path = self::dir()['path'] . '/' . $file;
			if ( is_file( $path ) ) {
				wp_delete_file( $path );
			}
		}
	}

	/**
	 * Remove the avatar of a user (back to Gravatar / the default picture).
	 */
	public static function delete( int $user_id ): void {
		self::delete_file( $user_id );
		delete_user_meta( $user_id, self::META );
	}
}
