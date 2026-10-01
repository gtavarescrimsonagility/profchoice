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
	 * [--message=<message>]
	 * : Commit message.
	 * ---
	 * default: Update content
	 * ---
	 *
	 * [--[no-]commit]
	 * : Commit and push the bundle worktree. Default: true; --no-commit only writes the files.
	 *
	 * [--[no-]push]
	 * : Push after committing. Default: true.
	 *
	 * ## EXAMPLES
	 *
	 *     studio wp bundle export
	 *     studio wp bundle export --message="Update homepage hero"
	 *     studio wp bundle export --no-commit
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
		$wxr = $this->add_term_meta( $wxr );
		// Drop export timestamps so unchanged content produces no diff.
		$wxr = preg_replace( '#(<!-- generator="[^"]*") created="[^"]*"#', '$1', $wxr );
		$wxr = preg_replace( '#\n<pubDate>[^<]*</pubDate>(?=[\s\S]*?<item>)#', '', $wxr, 1 );
		file_put_contents( $dir . '/content.xml', $wxr . "\n" );

		WP_CLI::success( sprintf( 'Exported %d posts and %d media files to %s', count( $post_ids ), count( $media ), $dir ) );

		if ( WP_CLI\Utils\get_flag_value( $assoc_args, 'commit', true ) ) {
			$this->publish( $dir, $assoc_args['message'], WP_CLI\Utils\get_flag_value( $assoc_args, 'push', true ) );
		}
	}

	private function publish( $dir, $message, $push ) {
		$git = 'git -C ' . escapeshellarg( $dir ) . ' ';

		$this->git( $git . 'add -A' );
		if ( '' === trim( $this->git( $git . 'status --porcelain' ) ) ) {
			WP_CLI::success( 'No content changes to commit.' );
			return;
		}

		$this->git( $git . 'commit -q -m ' . escapeshellarg( $message ) );
		WP_CLI::success( 'Committed: ' . trim( $this->git( $git . 'log -1 --format="%h %s"' ) ) );

		if ( $push ) {
			$this->git( $git . 'push -q' );
			WP_CLI::success( 'Pushed ' . trim( $this->git( $git . 'rev-parse --abbrev-ref HEAD' ) ) . '.' );
		}
	}

	private function git( $command ) {
		$result = WP_CLI::launch( $command, false, true );
		if ( 0 !== $result->return_code ) {
			WP_CLI::error( trim( $result->stderr ) ?: "Command failed: $command" );
		}
		return $result->stdout;
	}

	/**
	 * Published content (with WooCommerce products when it is active) plus the
	 * active theme's custom CSS and global styles.
	 */
	private function content_ids() {
		$types = array( 'page', 'post', 'wp_block', 'wp_navigation', 'wp_template', 'wp_template_part' );
		if ( post_type_exists( 'product' ) ) {
			$types[] = 'product';
		}
		$ids = get_posts( array(
			'post_type'   => $types,
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
	 * Attachments referenced by the content (featured images, block IDs, URLs)
	 * and by product categories.
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

		// Product category images (term meta), which the content may not use.
		if ( taxonomy_exists( 'product_cat' ) ) {
			foreach ( get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false, 'fields' => 'ids' ) ) as $term_id ) {
				$found[] = (int) get_term_meta( $term_id, 'thumbnail_id', true );
			}
		}

		$media = array();
		foreach ( array_unique( array_filter( $found ) ) as $id ) {
			$file = get_post_meta( $id, '_wp_attached_file', true );
			if ( 'attachment' === get_post_type( $id ) && $file && is_file( "$uploads/$file" ) ) {
				$media[ $id ] = $file;
			}
		}

		return $media;
	}

	/**
	 * `wp export` leaves out term meta; add the product categories' (image,
	 * order, display type) as <wp:termmeta>, which the WordPress Importer
	 * reads. Attachment IDs stay valid when the import keeps the original IDs
	 * (the blueprint resets the tables first).
	 */
	private function add_term_meta( $wxr ) {
		if ( ! taxonomy_exists( 'product_cat' ) ) {
			return $wxr;
		}
		foreach ( get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) ) as $term ) {
			$meta = '';
			foreach ( get_term_meta( $term->term_id ) as $key => $values ) {
				// Counts are a WooCommerce cache it rebuilds.
				if ( 0 === strpos( $key, 'product_count_' ) ) {
					continue;
				}
				foreach ( $values as $value ) {
					$meta .= "\t<wp:termmeta>\n\t\t<wp:meta_key><![CDATA[{$key}]]></wp:meta_key>\n\t\t<wp:meta_value><![CDATA[{$value}]]></wp:meta_value>\n\t</wp:termmeta>\n";
				}
			}
			if ( '' === $meta ) {
				continue;
			}
			$wxr = preg_replace(
				'#(<wp:term>(?:(?!</wp:term>).)*?<wp:term_taxonomy>(?:<!\[CDATA\[)?product_cat(?:\]\]>)?</wp:term_taxonomy>(?:(?!</wp:term>).)*?<wp:term_slug>(?:<!\[CDATA\[)?' . preg_quote( $term->slug, '#' ) . '(?:\]\]>)?</wp:term_slug>(?:(?!</wp:term>).)*?)(</wp:term>)#s',
				'$1' . $meta . '$2',
				$wxr,
				1
			);
		}
		return $wxr;
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
