<?php
/**
 * Twenty Twenty-Four functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package Twenty Twenty-Four
 * @since Twenty Twenty-Four 1.0
 */

/**
 * Register block styles.
 */

if (!function_exists('twentytwentyfour_block_styles')):
	/**
	 * Register custom block styles
	 *
	 * @since Twenty Twenty-Four 1.0
	 * @return void
	 */
	function twentytwentyfour_block_styles()
	{

		register_block_style(
			'core/details',
			array(
				'name' => 'arrow-icon-details',
				'label' => __('Arrow icon', 'twentytwentyfour'),
				/*
				 * Styles for the custom Arrow icon style of the Details block
				 */
				'inline_style' => '
				.is-style-arrow-icon-details {
					padding-top: var(--wp--preset--spacing--10);
					padding-bottom: var(--wp--preset--spacing--10);
				}

				.is-style-arrow-icon-details summary {
					list-style-type: "\2193\00a0\00a0\00a0";
				}

				.is-style-arrow-icon-details[open]>summary {
					list-style-type: "\2192\00a0\00a0\00a0";
				}',
			)
		);
		register_block_style(
			'core/post-terms',
			array(
				'name' => 'pill',
				'label' => __('Pill', 'twentytwentyfour'),
				/*
				 * Styles variation for post terms
				 * https://github.com/WordPress/gutenberg/issues/24956
				 */
				'inline_style' => '
				.is-style-pill a,
				.is-style-pill span:not([class], [data-rich-text-placeholder]) {
					display: inline-block;
					background-color: var(--wp--preset--color--base-2);
					padding: 0.375rem 0.875rem;
					border-radius: var(--wp--preset--spacing--20);
				}

				.is-style-pill a:hover {
					background-color: var(--wp--preset--color--contrast-3);
				}',
			)
		);
		register_block_style(
			'core/list',
			array(
				'name' => 'checkmark-list',
				'label' => __('Checkmark', 'twentytwentyfour'),
				/*
				 * Styles for the custom checkmark list block style
				 * https://github.com/WordPress/gutenberg/issues/51480
				 */
				'inline_style' => '
				ul.is-style-checkmark-list {
					list-style-type: "\2713";
				}

				ul.is-style-checkmark-list li {
					padding-inline-start: 1ch;
				}',
			)
		);
		register_block_style(
			'core/navigation-link',
			array(
				'name' => 'arrow-link',
				'label' => __('With arrow', 'twentytwentyfour'),
				/*
				 * Styles for the custom arrow nav link block style
				 */
				'inline_style' => '
				.is-style-arrow-link .wp-block-navigation-item__label:after {
					content: "\2197";
					padding-inline-start: 0.25rem;
					vertical-align: middle;
					text-decoration: none;
					display: inline-block;
				}',
			)
		);
		register_block_style(
			'core/heading',
			array(
				'name' => 'asterisk',
				'label' => __('With asterisk', 'twentytwentyfour'),
				'inline_style' => "
				.is-style-asterisk:before {
					content: '';
					width: 1.5rem;
					height: 3rem;
					background: var(--wp--preset--color--contrast-2, currentColor);
					clip-path: path('M11.93.684v8.039l5.633-5.633 1.216 1.23-5.66 5.66h8.04v1.737H13.2l5.701 5.701-1.23 1.23-5.742-5.742V21h-1.737v-8.094l-5.77 5.77-1.23-1.217 5.743-5.742H.842V9.98h8.162l-5.701-5.7 1.23-1.231 5.66 5.66V.684h1.737Z');
					display: block;
				}

				/* Hide the asterisk if the heading has no content, to avoid using empty headings to display the asterisk only, which is an A11Y issue */
				.is-style-asterisk:empty:before {
					content: none;
				}

				.is-style-asterisk:-moz-only-whitespace:before {
					content: none;
				}

				.is-style-asterisk.has-text-align-center:before {
					margin: 0 auto;
				}

				.is-style-asterisk.has-text-align-right:before {
					margin-left: auto;
				}

				.rtl .is-style-asterisk.has-text-align-left:before {
					margin-right: auto;
				}",
			)
		);
	}
endif;

add_action('init', 'twentytwentyfour_block_styles');

function add_custom_roles()
{
	add_role('cool_kid', 'Cool Kid', array(
		'read' => true,
		'view_own_character' => true
	));

	add_role('cooler_kid', 'Cooler Kid', array(
		'read' => true,
		'view_own_character' => true,
		'view_all_characters' => true
	));

	add_role('coolest_kid', 'Coolest Kid', array(
		'read' => true,
		'view_own_character' => true,
		'view_all_characters' => true
	));
}
register_activation_hook(__FILE__, 'add_custom_roles');


// Check if user has permission to view all characters
function current_user_can_view_all_characters()
{
	$user = wp_get_current_user();
	$allowed_roles = array('cooler_kid', 'coolest_kid');
	return array_intersect($allowed_roles, $user->roles);
}

// Redirect users to appropriate pages based on their role
function redirect_users_based_on_role()
{
	if (is_page('character') && !is_user_logged_in()) {
		wp_redirect(home_url('/login'));
		exit;
	}
}
add_action('template_redirect', 'redirect_users_based_on_role');

// Add custom capabilities to roles
function add_custom_capabilities()
{
	$role = get_role('cool_kid');
	$role->add_cap('view_own_character');

	$role = get_role('cooler_kid');
	$role->add_cap('view_own_character');
	$role->add_cap('view_all_characters');

	$role = get_role('coolest_kid');
	$role->add_cap('view_own_character');
	$role->add_cap('view_all_characters');
}
add_action('init', 'add_custom_capabilities');

function register_login_logout_button_block() {
    register_block_type('my-theme/login-logout-button', array(
        'render_callback' => 'render_login_logout_button',
        'attributes' => array(
            'className' => array(
                'type' => 'string',
                'default' => '',
            ),
        ),
    ));
}
add_action('init', 'register_login_logout_button_block');

function render_login_logout_button($attributes) {
    ob_start();
    
    $class_name = isset($attributes['className']) ? $attributes['className'] : '';
    
    echo '<div class="login-logout-wrapper ' . esc_attr($class_name) . '">';
    
    if (is_user_logged_in()) {
        $user = wp_get_current_user();
        if (!current_user_can('administrator')) {
            echo '<a href="' . esc_url(wp_logout_url(home_url('/'))) . '" class="logout-button">Log Out</a>';
        }
    } else {
        if (!is_admin()) {
            echo '<a href="' . esc_url(home_url('/login')) . '" class="login-button">Log In</a>';
        }
    }
    
    echo '</div>';
    
    return ob_get_clean();
}

// Handle login/logout redirects
function setup_login_logout_handling() {
    add_filter('login_url', 'custom_login_url', 10, 2);
}

function custom_login_url($login_url, $redirect) {
    if (current_user_can('administrator') || is_admin()) {
        return $login_url;
    }
    return home_url('/login');
}
add_action('init', 'setup_login_logout_handling');

// Register block in JavaScript
function enqueue_login_logout_block_editor_assets() {
    wp_enqueue_script(
        'login-logout-button-block',
        get_template_directory_uri() . '/js/login-logout-block.js',
        array('wp-blocks', 'wp-element')
    );
}
add_action('enqueue_block_editor_assets', 'enqueue_login_logout_block_editor_assets');


/**
 * Enqueue block stylesheets.
 */

if (!function_exists('twentytwentyfour_block_stylesheets')):
	/**
	 * Enqueue custom block stylesheets
	 *
	 * @since Twenty Twenty-Four 1.0
	 * @return void
	 */
	function twentytwentyfour_block_stylesheets()
	{
		/**
		 * The wp_enqueue_block_style() function allows us to enqueue a stylesheet
		 * for a specific block. These will only get loaded when the block is rendered
		 * (both in the editor and on the front end), improving performance
		 * and reducing the amount of data requested by visitors.
		 *
		 * See https://make.wordpress.org/core/2021/12/15/using-multiple-stylesheets-per-block/ for more info.
		 */
		wp_enqueue_block_style(
			'core/button',
			array(
				'handle' => 'twentytwentyfour-button-style-outline',
				'src' => get_parent_theme_file_uri('assets/css/button-outline.css'),
				'ver' => wp_get_theme(get_template())->get('Version'),
				'path' => get_parent_theme_file_path('assets/css/button-outline.css'),
			)
		);
	}
endif;

add_action('init', 'twentytwentyfour_block_stylesheets');

/**
 * Register pattern categories.
 */

if (!function_exists('twentytwentyfour_pattern_categories')):
	/**
	 * Register pattern categories
	 *
	 * @since Twenty Twenty-Four 1.0
	 * @return void
	 */
	function twentytwentyfour_pattern_categories()
	{

		register_block_pattern_category(
			'twentytwentyfour_page',
			array(
				'label' => _x('Pages', 'Block pattern category', 'twentytwentyfour'),
				'description' => __('A collection of full page layouts.', 'twentytwentyfour'),
			)
		);
	}
endif;

add_action('init', 'twentytwentyfour_pattern_categories');

