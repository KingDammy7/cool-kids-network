<?php
/**
 * Plugin Name: Cool Kids Network
 * Description: A user management system with character creation and API endpoints for role updates.
 * Version: 1.0.0
 * Author: Damilola Ayodele
 */

// Prevent direct access
defined('ABSPATH') || exit;

class CharacterDataHandler
{
    private $character_id;
    private $meta_cache;

    public function __construct($character_id)
    {
        $this->character_id = $character_id;
        $this->load_meta();
    }

    private function load_meta()
    {
        $this->meta_cache = get_post_meta($this->character_id);
    }

    public function get_name()
    {
        return sprintf(
            '%s %s',
            $this->meta_cache['first_name'][0] ?? '',
            $this->meta_cache['last_name'][0] ?? ''
        );
    }

    public function get_data()
    {
        // Get current user and their role
        $current_user = wp_get_current_user();
        $user_role = $current_user->roles[0] ?? '';

        // Get the character's author
        $author_id = get_post_field('post_author', $this->character_id);

        // Check if user is viewing their own character
        $viewing_own_character = ($author_id == $current_user->ID);

        // Base data structure
        $data = [
            'first_name' => $this->meta_cache['first_name'][0] ?? '',
            'last_name' => $this->meta_cache['last_name'][0] ?? '',
        ];

        // Logic for viewing character details based on roles
        if ($viewing_own_character) {
            // Users can always see all details of their own character
            $data['email'] = $this->meta_cache['email'][0] ?? '';
            $data['country'] = $this->meta_cache['country'][0] ?? '';
            $data['role'] = ucwords(str_replace('_', ' ', $user_role));
            $data['bio'] = $this->meta_cache['bio'][0] ?? '';
        } else {
            // Access rules for viewing other characters
            switch ($user_role) {
                case 'coolest_kid':
                    // Coolest kids can see everything
                    $data['email'] = $this->meta_cache['email'][0] ?? '';
                    $data['country'] = $this->meta_cache['country'][0] ?? '';
                    $data['role'] = ucwords(str_replace('_', ' ', get_user_role($author_id)));
                    $data['bio'] = $this->meta_cache['bio'][0] ?? '';
                    break;

                case 'cooler_kid':
                    // Cooler kids can only see name and country
                    $data['country'] = $this->meta_cache['country'][0] ?? '';
                    break;

                case 'cool_kid':
                    // Cool kids can't see other characters' details
                    return null;
                    break;
            }
        }

        return $data;
    }
}

// Helper function to get user role
function get_user_role($user_id)
{
    $user = get_user_by('id', $user_id);
    return $user ? array_shift($user->roles) : '';
}




class CoolKidsNetwork
{
    private static $instance = null;

    // Constructor
    private function __construct()
    {
        $this->init();
    }

    private function __clone()
    {
    }

    // Private wakeup method to prevent unserializing
    private function __wakeup()
    {
    }

    // Public static method to get the single instance
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // Initialize plugin
    private function init()
    {
        // Add hooks
        add_action('init', [$this, 'register_custom_roles']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('init', [$this, 'register_custom_post_type']);
        add_shortcode('cool_kids_signup', [$this, 'render_signup_form']);
        add_action('wp_ajax_ckn_register_user', [$this, 'handle_registration']);
        add_action('wp_ajax_nopriv_ckn_register_user', [$this, 'handle_registration']);
        add_action('rest_api_init', [$this, 'register_api_endpoints']);
        add_filter('authenticate', [$this, 'authenticate_without_password'], 10, 3);
        add_action('admin_init', [$this, 'create_character_page']);
        add_filter('template_include', [$this, 'load_character_template']);
    }

    public function create_character_page()
    {
        // Check if the character page already exists
        $character_page = get_page_by_path('character');

        if (!$character_page) {
            // Create the character page
            $page_data = array(
                'post_title' => 'Character',
                'post_content' => '',
                'post_status' => 'publish',
                'post_type' => 'page',
                'post_name' => 'character'
            );

            wp_insert_post($page_data);
        }
    }

    public function load_character_template($template)
    {
        if (is_page('character')) {
            $new_template = plugin_dir_path(__FILE__) . 'templates/character-template.php';
            if (file_exists($new_template)) {
                return $new_template;
            }
        }
        return $template;
    }

    // Register custom user roles
    public function register_custom_roles()
    {
        add_role('cool_kid', 'Cool Kid', [
            'read' => true,
            'view_own_character' => true
        ]);

        add_role('cooler_kid', 'Cooler Kid', [
            'read' => true,
            'view_own_character' => true,
            'view_others_basic' => true  // For name and country only
        ]);

        add_role('coolest_kid', 'Coolest Kid', [
            'read' => true,
            'view_own_character' => true,
            'view_others_basic' => true,
            'view_others_private' => true  // For email and role
        ]);
    }

    // Register character custom post type
    public function register_custom_post_type()
    {
        register_post_type('character', [
            'labels' => [
                'name' => 'Characters',
                'singular_name' => 'Character'
            ],
            'public' => true,
            'has_archive' => true,
            'supports' => ['title', 'custom-fields'],
            'show_in_rest' => true
        ]);
    }

    public function get_all_characters(WP_REST_Request $request)
    {
        $current_user = wp_get_current_user();
        if (!is_user_logged_in()) {
            return new WP_Error('not_logged_in', 'You must be logged in to view characters', ['status' => 401]);
        }

        // Get current user's role
        $user_role = $current_user->roles[0] ?? '';

        // Check if user has necessary permissions
        if (!in_array($user_role, ['cooler_kid', 'coolest_kid'])) {
            return new WP_Error('insufficient_permissions', 'You do not have permission to view other characters', ['status' => 403]);
        }

        $characters = get_posts([
            'post_type' => 'character',
            'posts_per_page' => -1
        ]);

        $data = [];
        foreach ($characters as $character) {
            $handler = new CharacterDataHandler($character->ID);
            $data[] = $handler->get_data($user_role);
        }

        return new WP_REST_Response($data, 200);
    }


    // Updated handle_registration method
    public function handle_registration()
    {
        check_ajax_referer('ckn_nonce', 'nonce');

        $email = sanitize_email($_POST['email']);

        if (!is_email($email)) {
            wp_send_json_error('Invalid email address');
        }

        if (email_exists($email)) {
            wp_send_json_error('Email already registered');
        }

        $random_user = $this->get_random_user_data();
        if (!$random_user) {
            wp_send_json_error('Failed to generate character data');
        }

        $user_id = wp_create_user($email, wp_generate_password(), $email);

        if (is_wp_error($user_id)) {
            wp_send_json_error('Failed to create user');
        }

        $user = new WP_User($user_id);
        $user->set_role('cool_kid');

        $character_id = wp_insert_post([
            'post_type' => 'character',
            'post_title' => $random_user['name']['first'] . ' ' . $random_user['name']['last'],
            'post_status' => 'publish',
            'post_author' => $user_id
        ]);

        if (is_wp_error($character_id)) {
            wp_delete_user($user_id);
            wp_send_json_error('Failed to create character');
        }

        // Store character data
        $meta_data = [
            'first_name' => $random_user['name']['first'],
            'last_name' => $random_user['name']['last'],
            'country' => $random_user['location']['country'],
            'email' => $email,
            'character_id' => $character_id
        ];

        foreach ($meta_data as $key => $value) {
            update_post_meta($character_id, $key, $value);
        }

        $this->log_event('user_registration', [
            'user_id' => $user_id,
            'character_id' => $character_id,
            'email' => $email
        ]);

        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id);

        wp_send_json_success([
            'redirect' => home_url('/characters/'),
            'message' => 'Registration successful!'
        ]);
    }



    // Register API endpoints
    public function register_api_endpoints()
    {
        register_rest_route('cool-kids/v1', '/update-role', [
            'methods' => 'POST',
            'callback' => [$this, 'update_user_role'],
            'permission_callback' => [$this, 'verify_api_request'],
            'args' => [
                'identifier_type' => [
                    'required' => true,
                    'type' => 'string',
                    'enum' => ['email', 'name']
                ],
                'identifier_value' => [
                    'required' => true,
                    'type' => 'string'
                ],
                'first_name' => [
                    'required' => false,
                    'type' => 'string'
                ],
                'last_name' => [
                    'required' => false,
                    'type' => 'string'
                ],
                'new_role' => [
                    'required' => true,
                    'type' => 'string',
                    'enum' => ['cool_kid', 'cooler_kid', 'coolest_kid']
                ]
            ]
        ]);

        register_rest_route('cool-kids/v1', '/characters', [
            'methods' => 'GET',
            'callback' => [$this, 'get_all_characters'],
            'permission_callback' => function () {
                $user = wp_get_current_user();
                return is_user_logged_in() &&
                    (in_array('cooler_kid', $user->roles) ||
                        in_array('coolest_kid', $user->roles));
            }
        ]);

        register_rest_route('cool-kids/v1', '/users', [
            'methods' => 'GET',
            'callback' => [$this, 'get_all_users'],
            'permission_callback' => function () {
                return current_user_can('view_others_basic');
            }
        ]);
    }

    public function get_all_users(WP_REST_Request $request)
    {
        $current_user = wp_get_current_user();
        $show_private = in_array('coolest_kid', (array) $current_user->roles);

        $users = get_users([
            'role__in' => ['cool_kid', 'cooler_kid', 'coolest_kid']
        ]);

        $data = [];
        foreach ($users as $user) {
            $character_id = $this->get_user_character_id($user->ID);
            $character = new CharacterDataHandler($character_id);
            $data[] = $character->get_data($show_private);
        }

        return new WP_REST_Response($data, 200);
    }




    // Verify API request
    public function verify_api_request(WP_REST_Request $request)
    {
        $api_key = $request->get_header('X-API-Key');

        if (!$api_key) {
            return new WP_Error('no_api_key', 'Missing API key', ['status' => 401]);
        }

        $valid_api_key = get_option('ckn_api_key', false);

        if (!$valid_api_key || $api_key !== $valid_api_key) {
            return new WP_Error('invalid_api_key', 'Invalid API key', ['status' => 401]);
        }

        return true;
    }

    // Enqueue necessary CSS and JavaScript
    public function enqueue_assets()
    {
        wp_enqueue_style('cool-kids-network', plugin_dir_url(__FILE__) . 'assets/css/style.css', [], '1.0.3');
        wp_enqueue_script('cool-kids-network', plugin_dir_url(__FILE__) . 'assets/js/main.js', ['jquery'], '1.0.0', true);
        wp_localize_script('cool-kids-network', 'ckn_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ckn_nonce')
        ]);
    }

    // Render signup form

    public function render_signup_form()
    {
        if (is_user_logged_in()) {
            return '<p>You are already registered.</p>';
        }

        ob_start();
        ?>
        <div class="sign-up-wrapper">
            <div class="ckn-signup-form">
                <div class="form-header">
                    <h2>Welcome to Cool Kids Network </h2>
                    <h5>Please create an account to continue</h5>
                </div>

                <form id="ckn-signup" method="post">
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" required placeholder="Enter your email">
                    </div>
                    <div class="response-message"></div>
                    <button type="submit">Confirm</button>
                </form>
                <p>After registration, you will be redirected to your character page.</p>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    // Helper method to get random user data
    private function get_random_user_data()
    {
        $response = wp_remote_get('https://randomuser.me/api/');

        if (is_wp_error($response)) {
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        return isset($data['results'][0]) ? $data['results'][0] : false;
    }

    // Update user role endpoint
    public function update_user_role(WP_REST_Request $request)
    {
        global $wpdb;

        $identifier_type = $request->get_param('identifier_type');
        $identifier_value = $request->get_param('identifier_value');
        $new_role = $request->get_param('new_role');

        $user = null;

        if ($identifier_type === 'email') {
            $user = get_user_by('email', $identifier_value);
        } else if ($identifier_type === 'name') {
            $first_name = $request->get_param('first_name');
            $last_name = $request->get_param('last_name');

            if (!$first_name || !$last_name) {
                return new WP_Error('missing_parameters', 'First name and last name are required when using name identifier', ['status' => 400]);
            }

            $character = get_posts([
                'post_type' => 'character',
                'meta_query' => [
                    'relation' => 'AND',
                    ['key' => 'first_name', 'value' => $first_name, 'compare' => '='],
                    ['key' => 'last_name', 'value' => $last_name, 'compare' => '=']
                ],
                'posts_per_page' => 1
            ]);

            if (!empty($character)) {
                $user = get_user_by('id', $character[0]->post_author);
            }
        }

        if (!$user) {
            return new WP_Error('user_not_found', 'User not found', ['status' => 404]);
        }

        $user->set_role($new_role);
        $this->log_role_change($user->ID, $new_role);

        return [
            'success' => true,
            'message' => 'Role updated successfully',
            'data' => [
                'user_id' => $user->ID,
                'new_role' => $new_role
            ]
        ];
    }

    // Log role changes
    private function log_role_change($user_id, $new_role)
    {
        $log_entry = [
            'user_id' => $user_id,
            'new_role' => $new_role,
            'timestamp' => current_time('mysql'),
            'ip_address' => $_SERVER['REMOTE_ADDR']
        ];

        update_option(
            'ckn_role_change_log',
            array_merge(
                get_option('ckn_role_change_log', []),
                [$log_entry]
            )
        );
    }

    private function log_event($type, $data)
    {
        $log = [
            'timestamp' => current_time('mysql'),
            'type' => $type,
            'data' => $data,
            'ip' => $_SERVER['REMOTE_ADDR']
        ];

        $logs = get_option('ckn_events_log', []);
        array_push($logs, $log);

        // Keep only last 1000 entries
        if (count($logs) > 1000) {
            $logs = array_slice($logs, -1000);
        }

        update_option('ckn_events_log', $logs);

        // Also write to WordPress debug log if enabled
        if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            error_log(sprintf(
                '[Cool Kids Network] %s: %s',
                $type,
                json_encode($data)
            ));
        }
    }

    public function authenticate_without_password($user, $username, $password)
    {
        if (!empty($username)) {
            $user = get_user_by('email', $username);
            if ($user) {
                return $user;
            }
        }
        return null;
    }


    // Generate and store API key
    public function generate_api_key()
    {
        $api_key = wp_generate_password(32, false);
        update_option('ckn_api_key', $api_key);
        return $api_key;
    }

    // Singleton instance
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}

class CoolKidsRoleManager
{
    public function __construct()
    {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('wp_ajax_update_user_role', [$this, 'handle_role_update']);
    }

    public function add_admin_menu()
    {
        add_menu_page(
            'Cool Kids Role Manager',
            'Cool Kids Roles',
            'manage_options',
            'cool-kids-roles',
            [$this, 'render_admin_page'],
            'dashicons-groups',
            30
        );
    }

    public function enqueue_admin_assets($hook)
    {
        if ($hook != 'toplevel_page_cool-kids-roles') {
            return;
        }

        wp_enqueue_style('cool-kids-admin', plugins_url('assets/css/admin.css', __FILE__));
        wp_enqueue_script('cool-kids-admin', plugins_url('assets/js/admin.js', __FILE__), ['jquery'], '1.0.0', true);
        wp_localize_script('cool-kids-admin', 'cknAdmin', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ckn_role_update')
        ]);
    }

    public function render_admin_page()
    {
        // Get all users with Cool Kids roles
        $users = get_users([
            'role__in' => ['cool_kid', 'cooler_kid', 'coolest_kid']
        ]);

        ?>
        <div class="wrap">
            <h1>Cool Kids Role Manager</h1>

            <div class="tablenav top">
                <div class="alignleft actions">
                    <select id="bulk-action-selector">
                        <option value="">Bulk Actions</option>
                        <option value="cool_kid">Make Cool Kid</option>
                        <option value="cooler_kid">Make Cooler Kid</option>
                        <option value="coolest_kid">Make Coolest Kid</option>
                    </select>
                    <button class="button" id="bulk-apply">Apply</button>
                </div>
            </div>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="select-all"></th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Current Role</th>
                        <th>Country</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user):
                        $character_id = $this->get_user_character_id($user->ID);
                        $country = get_post_meta($character_id, 'country', true);
                        $current_role = array_shift($user->roles);
                        ?>
                        <tr data-user-id="<?php echo esc_attr($user->ID); ?>">
                            <td><input type="checkbox" class="user-select"></td>
                            <td><?php echo esc_html($user->display_name); ?></td>
                            <td><?php echo esc_html($user->user_email); ?></td>
                            <td class="user-role"><?php echo esc_html(ucwords(str_replace('_', ' ', $current_role))); ?></td>
                            <td><?php echo esc_html($country); ?></td>
                            <td>
                                <select class="role-select">
                                    <option value="cool_kid" <?php selected($current_role, 'cool_kid'); ?>>Cool Kid</option>
                                    <option value="cooler_kid" <?php selected($current_role, 'cooler_kid'); ?>>Cooler Kid</option>
                                    <option value="coolest_kid" <?php selected($current_role, 'coolest_kid'); ?>>Coolest Kid
                                    </option>
                                </select>
                                <button class="button update-role">Update</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    private function get_user_character_id($user_id)
    {
        $characters = get_posts([
            'post_type' => 'character',
            'author' => $user_id,
            'posts_per_page' => 1,
            'fields' => 'ids'
        ]);
        return !empty($characters) ? $characters[0] : 0;
    }

    public function handle_role_update()
    {
        check_ajax_referer('ckn_role_update', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $user_id = intval($_POST['user_id']);
        $new_role = sanitize_text_field($_POST['new_role']);

        if (!in_array($new_role, ['cool_kid', 'cooler_kid', 'coolest_kid'])) {
            wp_send_json_error('Invalid role');
        }

        $user = get_user_by('id', $user_id);
        if (!$user) {
            wp_send_json_error('User not found');
        }

        $user->set_role($new_role);

        // Log the role change
        $this->log_role_change($user_id, $new_role);

        wp_send_json_success([
            'message' => 'Role updated successfully',
            'new_role' => ucwords(str_replace('_', ' ', $new_role))
        ]);
    }

    private function log_role_change($user_id, $new_role)
    {
        $log_entry = [
            'user_id' => $user_id,
            'new_role' => $new_role,
            'changed_by' => get_current_user_id(),
            'timestamp' => current_time('mysql'),
            'ip_address' => $_SERVER['REMOTE_ADDR']
        ];

        $logs = get_option('ckn_role_change_log', []);
        array_unshift($logs, $log_entry);
        update_option('ckn_role_change_log', array_slice($logs, 0, 1000));
    }

}


// Initialize the plugin
function cool_kids_network_init()
{
    return CoolKidsNetwork::get_instance();
}

// Start the plugin
cool_kids_network_init();

function init()
{
    // Your existing init code...
    new CoolKidsRoleManager();
}
