<?php
/*
Plugin Name: Signature Generate App
Description: Allow to Generate Signatures
Version: 1.0
Author: Alex Nguyen
License: GPLv3
*/

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Allow operation in the Admin Panel.
 */
if (is_admin()) {
    require_once dirname(__FILE__) . '/inc/signatures.class.php';
    // Initialize Signature Class
    $sig = new SignaturesGenerate();
}
