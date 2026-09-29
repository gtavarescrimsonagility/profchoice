<?php
/**
 * WP-CLI command that saves the Studio site content into the `bundle` branch
 * worktree, which the Playground blueprint imports.
 *
 * Loaded through wp-cli.yml: `studio wp bundle export`.
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

class ProfChoice_Bundle_Command {

	const RAW_URL = 'https://raw.githubusercontent.com/gtavarescrimsonagility/profchoice/bundle';

	/**
	 * Exports pages, block content, custom CSS and the media they use.
	 *
	 * Writes `content.xml` (WXR) and `uploads/` into the bundle worktree, with
	 * upload URLs rewritten to the bundle branch on raw.githubusercontent.com.
	 *
	 * ## OPTIONS
	 *
	 * [--dir=<dir>]
	 * : Bundle worktree path. Defaults to ../profchoice-bundle next to the site.
	 *
	 * ## EXAMPLES
	 *
	 *     studio wp bundle export
	 *
	 * @when after_wp_load
	 */
	public function export( $args, $assoc_args ) {
		$dir = rtrim( $assoc_args['dir'] ?? dirname( ABSPATH ) . '/profchoice-bundle', '/' );
		if ( ! is_dir( $dir . '/.git' ) && ! is_file( $dir . '/.git' ) ) {
			WP_CLI::error( "Not a git worktree: $dir" );
		}

		$post_ids = $this->content_ids();
		$media    = $this->media_ids( $post_ids );
		$ids      = array_merge( $post_ids, array_keys( $media ) );
		if ( ! $post_ids ) {
			WP_CLI::error( 'Nothing to export.' );
		}

		$this->reset_dir( $dir . '/uploads' );
		foreach ( $media as $file ) {
			$target = $dir . '/uploads/' . $file;
			wp_mkdir_p( dirname( $target ) );
			copy( wp_get_upload_dir()['basedir'] . '/' . $file, $target );
		}

		$wxr = WP_CLI::runcommand(
			'export --stdout --post__in=' . implode( ',', $ids ),
			array( 'return' => true, 'exit_error' => true )
		);
		// Content keeps the URL it was saved with (e.g. localhost:<port>), while
		// attachment URLs use siteurl, so match any host.
		$wxr = preg_replace( '#https?://[^/"\'\s<]+/wp-content/uploads/#', self::RAW_URL . '/uploads/', $wxr );
		file_put_contents( $dir . '/content.xml', $wxr . "\n" );

		WP_CLI::success( sprintf( 'Exported %d posts and %d media files to %s', count( $post_ids ), count( $media ), $dir ) );
		WP_CLI::log( "Review and publish: git -C $dir add -A && git -C $dir commit -m '...' && git -C $dir push" );
	}

	/**
	 * Published content plus the active theme's custom CSS and global styles.
	 */
	private function content_ids() {
		$ids = get_posts( array(
			'post_type'   => array( 'page', 'post', 'wp_block', 'wp_navigation', 'wp_template', 'wp_template_part' ),
			'post_status' => array( 'publish', 'private', 'draft' ),
			'numberposts' => -1,
			'fields'      => 'ids',
		) );

		$css = wp_get_custom_css_post();
		if ( $css ) {
			$ids[] = $css->ID;
		}

		$styles = WP_Theme_JSON_Resolver::get_user_global_styles_post_id();
		if ( $styles ) {
			$ids[] = $styles;
		}

		return array_map( 'intval', $ids );
	}

	/**
	 * Attachments referenced by the content (featured images, block IDs, URLs).
	 *
	 * @return array<int, string> Attachment ID => file path relative to uploads.
	 */
	private function media_ids( $post_ids ) {
		$uploads = wp_get_upload_dir()['basedir'];
		$found   = array();

		foreach ( $post_ids as $post_id ) {
			$content = get_post_field( 'post_content', $post_id );

			$thumbnail = (int) get_post_thumbnail_id( $post_id );
			if ( $thumbnail ) {
				$found[] = $thumbnail;
			}

			preg_match_all( '#"(?:id|mediaId)":(\d+)#', $content, $m );
			$found = array_merge( $found, array_map( 'intval', $m[1] ) );

			preg_match_all( '#/wp-content/uploads/([^"\'\s)?]+)#', $content, $m );
			foreach ( $m[1] as $path ) {
				// Resized variants (-1024x678) point to their original.
				$file = preg_replace( '#-\d+x\d+(\.\w+)$#', '$1', $path );
				$id   = attachment_url_to_postid( wp_get_upload_dir()['baseurl'] . '/' . $file );
				if ( $id ) {
					$found[] = $id;
				}
			}
		}

		$media = array();
		foreach ( array_unique( $found ) as $id ) {
			$file = get_post_meta( $id, '_wp_attached_file', true );
			if ( 'attachment' === get_post_type( $id ) && $file && is_file( "$uploads/$file" ) ) {
				$media[ $id ] = $file;
			}
		}

		return $media;
	}

	private function reset_dir( $dir ) {
		if ( is_dir( $dir ) ) {
			$files = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator( $dir, FilesystemIterator::SKIP_DOTS ),
				RecursiveIteratorIterator::CHILD_FIRST
			);
			foreach ( $files as $file ) {
				$file->isDir() ? rmdir( $file ) : unlink( $file );
			}
		}
		wp_mkdir_p( $dir );
	}
}

WP_CLI::add_command( 'bundle', 'ProfChoice_Bundle_Command' );
