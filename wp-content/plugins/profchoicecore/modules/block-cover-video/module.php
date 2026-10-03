<?php
/**
 * Module Name: Video Covers
 * Description: Autoplay, Loop, Muted and Lazy load settings for Cover videos and a Play button style.
 *
 * @package ProfChoiceCore
 */

namespace ProfChoiceCore;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/includes/CoverVideo.php';

CoverVideo::register_hooks();
