<?php
/*
Template Name: Login Page
*/

// Redirect if already logged in
if (is_user_logged_in()) {
    $characters_page = get_page_by_path('characters');
    if ($characters_page) {
        wp_redirect(site_url('?page_id=' . $characters_page->ID));
        exit;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email = sanitize_email($_POST['email']);

    // Check if user exists
    $user = get_user_by('email', $email);

    if ($user) {
        // Log the user in
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID);
        do_action('wp_login', $user->user_login, $user);

        // Redirect to character page
        $characters_page = get_page_by_path('characters');
        if ($characters_page) {
            wp_redirect(site_url('?page_id=' . $characters_page->ID));
            exit;
        }
    } else {
        $error_message = "No account found with this email address.";
    }
}

block_template_part('header');
get_header();
?>

<style>
    body {
        background-image: url('https://images.pexels.com/photos/194511/pexels-photo-194511.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=2');
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        background-color: #0a192f;
        /* Dark blue fallback */
    }

    .ckn-login-form {
        max-width: 500px;
        margin: 4rem auto;
        padding: 2.5rem;
        background: rgba(13, 28, 47, 0.9);
        /* Dark blue with transparency */
        border-radius: 12px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        color: white;
    }

    .form-header {
        text-align: center;
        margin-bottom: 2rem;
    }

    .form-header h2 {
        color: white;
        font-size: 2rem;
        margin-bottom: 0.5rem;
        font-weight: 600;
    }

    .form-header h5 {
        color: #a0aec0;
        font-weight: normal;
        margin-top: 0;
    }

    .form-group {
        margin-bottom: 1.5rem;
    }

    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        color: #a0aec0;
        font-size: 0.9rem;
    }

    .form-group input {
        width: 100%;
        padding: 0.75rem;
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 6px;
        color: white;
        font-size: 1rem;
        transition: all 0.3s ease;
    }

    .form-group input:focus {
        outline: none;
        border-color: #ff69b4;
        /* Pink accent color */
        background: rgba(255, 255, 255, 0.15);
    }

    button[type="submit"] {
        width: 100%;
        padding: 0.75rem;
        background: #ff69b4;
        /* Pink accent color */
        color: white;
        border: none;
        border-radius: 6px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    button[type="submit"]:hover {
        background: #ff4da6;
        transform: translateY(-1px);
    }

    .alert {
        padding: 1rem;
        border-radius: 6px;
        margin-bottom: 1rem;
        text-align: center;
    }

    .alert-danger {
        background: rgba(220, 38, 38, 0.2);
        color: #fecaca;
        border: 1px solid rgba(220, 38, 38, 0.3);
    }
</style>

<div class="ckn-login-form">
    <div class="form-header">
        <h2>Cool Kids Network Login Form</h2>
        <h5>Welcome back! Please login to continue</h5>
    </div>

    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger">
            <?php echo esc_html($error_message); ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" class="form-control" id="email" name="email" required
                value="<?php echo isset($_POST['email']) ? esc_attr($_POST['email']) : ''; ?>"
                placeholder="Enter your email">
        </div>

        <button type="submit">Login</button>
    </form>
</div>