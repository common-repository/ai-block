<?php
/**
 * Plugin Name: AI Block
 * Plugin URI: https://blockifywp.com/ai-block
 * Description: An intelligent block that helps you generate posts, pages or any kind of content with AI.
 * Version: 0.0.2
 * Author: Blockify
 * Author URI: https://blockifywp.com/
 * License: GPL-2.0-or-later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ai-block
 * Domain Path: /languages
 * Requires at least: 6.1
 * Requires PHP: 7.4
 * Tested up to: 6.1
 */

namespace Blockify\AIBlock;

use WP_REST_Request;
use WP_REST_Server;
use function add_action;
use function current_user_can;
use function get_option;
use function is_admin;
use function json_decode;
use function register_block_type;
use function register_rest_route;
use function register_setting;
use function wp_json_encode;
use function wp_remote_post;

const NS = __NAMESPACE__ . '\\';

add_action( 'init', NS . 'register_block' );
/**
 * Register block.
 *
 * @since 0.0.1
 *
 * @return void
 */
function register_block(): void {
	register_block_type( __DIR__ . '/build' );
}

add_action( 'rest_api_init', NS . 'register_endpoint' );
/**
 * Register endpoint.
 *
 * @since 0.0.1
 *
 * @return void
 */
function register_endpoint(): void {
	register_rest_route(
		'ai-block/v1',
		'completions',
		[
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => NS . 'send_request',
			'permission_callback' => fn() => current_user_can( 'edit_posts' ),
			'args'                => [],
		]
	);
}

/**
 * Send request to OpenAI API.
 *
 * @param WP_REST_Request $request The request object.
 *
 * @since 0.0.1
 *
 * @return string
 */
function send_request( WP_REST_Request $request ): string {
	$options    = get_option( 'aiBlock', [] );
	$prompt     = $request->get_param( 'prompt' ) ?? '';
	$max_tokens = (int) ( $request->get_param( 'max_tokens' ) ? $request->get_param( 'max_tokens' ) : $options['maxTokens'] ?? 25 );
	$api_key    = $request->get_param( 'api_key' ) ?? $options['apiKey'] ?? '';
	$base_url   = 'https://api.openai.com/v1/completions';
	$model_id   = 'text-davinci-003';

	if ( ! $api_key ) {
		return __( 'Please enter your API key.', 'ai-block' );
	}

	$options = [
		'method'  => 'POST',
		'headers' => [
			'Content-Type'  => 'application/json',
			'Authorization' => 'Bearer ' . $api_key,
		],
		'body'    => wp_json_encode(
			[
				'model'      => $model_id,
				'prompt'     => $prompt,
				'max_tokens' => $max_tokens,
			]
		),
	];

	$response = wp_remote_post( $base_url, $options );

	if ( is_wp_error( $response ) ) {
		$response = wp_remote_post( $base_url, $options );
	}

	if ( is_wp_error( $response ) ) {
		$response = wp_remote_post( $base_url, $options );
	}

	if ( is_wp_error( $response ) ) {
		return $response->get_error_message();
	}

	$text = json_decode( $response['body'], true )['choices'][0]['text'] ?? '';

	if ( $prompt === 'check' && $max_tokens === 2 ) {
		return $text ? 'valid' : 'invalid';
	}

	return $text;
}

add_action( 'admin_init', NS . 'register_settings' );
add_action( 'rest_api_init', NS . 'register_settings' );
/**
 * Register plugin settings.
 *
 * @since 0.0.1
 *
 * @return void
 */
function register_settings(): void {
	register_setting(
		'options',
		'aiBlock',
		[
			'description'  => __( 'AI Block.', 'blockify' ),
			'type'         => 'object',
			'show_in_rest' => [
				'schema' => [
					'type'       => 'object',
					'properties' => [
						'apiKey'       => [
							'type' => 'string',
						],
						'apiKeyStatus' => [
							'type' => 'string',
						],
						'maxTokens'    => [
							'type' => 'integer',
						],
					],
				],
			],
		]
	);
}
