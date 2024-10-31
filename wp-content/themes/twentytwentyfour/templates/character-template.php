<?php
/*
Template Name: Character Display
*/

if (!defined('ABSPATH')) {
    exit;
}

// Helper Functions
function get_unique_character_countries()
{
    global $wpdb;

    $results = $wpdb->get_col($wpdb->prepare("
        SELECT DISTINCT meta_value 
        FROM {$wpdb->postmeta} 
        WHERE meta_key = %s 
        AND meta_value != ''
        ORDER BY meta_value ASC
    ", 'country'));

    return array_filter($results);
}

function get_character_roles()
{
    return ['cool_kid', 'cooler_kid', 'coolest_kid'];
}

function get_user_character_id($user_id)
{
    $characters = get_posts([
        'post_type' => 'character',
        'author' => $user_id,
        'posts_per_page' => 1,
        'fields' => 'ids'
    ]);

    return !empty($characters) ? $characters[0] : 0;
}

function user_has_advanced_role($user = null)
{
    if (!$user) {
        $user = wp_get_current_user();
    }
    return array_intersect(['cooler_kid', 'coolest_kid'], (array) $user->roles);
}

function render_character_card($data, $classes = '')
{
    ?>
    <div class="character-card bg-white shadow rounded p-4 <?php echo esc_attr($classes); ?>">
        <?php foreach ($data as $key => $value):
            if (empty($value))
                continue;
            // Only show email for user's own character or users with advanced roles
            if ($key === 'email' && !user_has_advanced_role())
                continue;
            ?>
            <p class="mb-2">
                <strong class="text-gray-700"><?php echo esc_html(ucfirst(str_replace('_', ' ', $key))); ?>:</strong>
                <span class="text-gray-900"><?php echo esc_html($value); ?></span>
            </p>
        <?php endforeach; ?>
    </div>
    <?php
}

// Main Template
block_template_part('header');
get_header();


// Redirect if not logged in
if (!is_user_logged_in()) {
    wp_safe_redirect(home_url('/login'));
    exit;
}

$current_user = wp_get_current_user();
$page = max(1, get_query_var('paged', 1));
$per_page = apply_filters('ckn_characters_per_page', 10);

// Sanitize filters
$country_filter = isset($_GET['country']) ? sanitize_text_field($_GET['country']) : '';
$role_filter = isset($_GET['role']) ? sanitize_text_field($_GET['role']) : '';
$search_term = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';

?>

<div class="container">
    <?php
    // Get current user's character
    $character_id = get_user_character_id($current_user->ID);
    if ($character_id) {
        $character = new CharacterDataHandler($character_id);
        $user_data = $character->get_data(true); // Always show full data for own character
        ?>
        <div class="your-character-section">
            <h2 class="section-title">Your Character</h2>
            <?php render_character_card($user_data, 'border-t-4 border-blue-500'); ?>
        </div>
        <?php
    } else {
        ?>
        <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-8" role="alert">
            <p class="text-center">No character found for your account.</p>
        </div>
        <?php
    }
    ?>

    <?php if (user_has_advanced_role()): // Only show other characters for Cooler/Coolest Kids ?>

        <!-- Characters List -->
        <?php
        $query_args = [
            'post_type' => 'character',
            'posts_per_page' => $per_page,
            'paged' => $page,
            'meta_query' => []
        ];

        if ($country_filter) {
            $query_args['meta_query'][] = [
                'key' => 'country',
                'value' => $country_filter,
                'compare' => '='
            ];
        }

        if ($role_filter && in_array('coolest_kid', (array) $current_user->roles)) {
            // Only coolest_kid can filter by role
            $query_args['meta_query'][] = [
                'key' => 'role',
                'value' => $role_filter,
                'compare' => '='
            ];
        }

        if ($search_term) {
            $query_args['meta_query'][] = [
                'relation' => 'OR',
                ['key' => 'first_name', 'value' => $search_term, 'compare' => 'LIKE'],
                ['key' => 'last_name', 'value' => $search_term, 'compare' => 'LIKE']
            ];
        }

        if (!empty($query_args['meta_query'])) {
            $query_args['meta_query']['relation'] = 'AND';
        }

        $others_query = new WP_Query($query_args);
        ?>

        <div class="other-characters">
            <div class="flex justify-between items-center mb-4">
                <h3 class="section-title">All Characters</h3>
                <div class="characters-count">
                    Showing <?php echo $others_query->post_count; ?> of <?php echo $others_query->found_posts; ?> characters
                </div>
            </div>

            <?php if ($others_query->have_posts()): ?>
                <div class="characters-grid">
                    <?php
                    while ($others_query->have_posts()):
                        $others_query->the_post();
                        $character = new CharacterDataHandler(get_the_ID());
                        // Show full data only for coolest_kid role
                        $show_full_data = in_array('coolest_kid', (array) $current_user->roles);
                        // Check if this is the current user's character
                        $is_current_user = (get_post_field('post_author', get_the_ID()) == $current_user->ID);
                        // If it's current user's character, show full data and special styling
                        if ($is_current_user) {
                            $data = $character->get_data(true);
                            render_character_card($data, 'border-t-4 border-blue-500');
                        } else {
                            $data = $character->get_data($show_full_data);
                            render_character_card($data);
                        }
                    endwhile;
                    ?>
                </div>

                <?php if ($others_query->max_num_pages > 1): ?>
                    <div class="pagination mt-8 flex justify-center">
                        <?php
                        echo paginate_links([
                            'base' => add_query_arg('paged', '%#%'),
                            'format' => '',
                            'current' => $page,
                            'total' => $others_query->max_num_pages,
                            'prev_text' => '&laquo; Previous',
                            'next_text' => 'Next &raquo;',
                            'type' => 'list',
                            'end_size' => 2,
                            'mid_size' => 2
                        ]);
                        ?>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4" role="alert">
                    <p>No characters found matching your criteria.</p>
                </div>
            <?php endif;
            wp_reset_postdata();
            ?>
        </div>
    <?php endif; ?>

</div>