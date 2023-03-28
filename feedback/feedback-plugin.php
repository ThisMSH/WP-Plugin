<?php

/**
 * Plugin Name: Feedback
 * Description: A plugin that allows users to submit feedback on your website.
 * Author: El Mahdi S.H.
 * Version: 1.0.0
 * Text Domain: feedback
 * 
 */
if (!defined('ABSPATH')) {
    exit;
}

class Feedback
{
    public function __construct()
    {
        add_action('init', array($this, 'create_feedback'));
        add_action('rest_api_init', array($this, 'register_rest_api'));
        add_action('wp_enqueue_scripts', array($this, 'load_assets'));
        add_action('wp_footer', array($this, 'load_script'));
        add_action('add_meta_boxes', array($this, 'create_meta_box'));
        add_shortcode('feedback-form', array($this, 'load_shortcode'));
    }
    public function create_meta_box()
    {
        add_meta_box('feedback', 'Submission', array($this, 'display_submission'), 'Feedback_Form');
    }
    public function display_submission()
    {
        $post_metas = get_post_meta(get_the_ID());
        unset($post_metas['_edit_lock']);
        echo '<ul>';
        foreach ($post_metas as $meta => $value) {
            echo '<li><strong>' .  $meta . '</strong>' . ' : ' . $value[0] . '</li>';
        }
        echo '</ul>';
    }
    public function create_feedback()
    {
        $argm = array(
            'public' => true,
            'has_archive' => true,
            'supports' => false,
            'exclude_from_search' => true,
            'publicly_queryable' => false,
            'capability' => 'manage_options',
            'labels' => array(
                'name' => 'Feedbacks',
                'singular_name' => 'Feedback Form Entry'
            ),
            'menu_icon' => 'dashicons-media-text',
            'capability_type' => 'post',
            'capabilities' => array(
                'create_posts' => false,
            ),
            'map_meta_cap' => true,
        );
        register_post_type('Feedback_Form', $argm);
    }
    public function load_assets()
    {
        wp_enqueue_style(
            'feedback',
            plugin_dir_url(__FILE__) . 'css/feedback.css',
            array(),
            1,
            'all'
        );
        wp_enqueue_script(
            'feedback',
            plugin_dir_url(__FILE__) . 'js/feedback.js',
            array('jquery'),
            1,
            true
        );
    }
    public function load_shortcode()
    {
        $page_id = get_the_ID();
?>

    <div class="feedback">
        <h2>Feedback</h2>
        <p>Your feedback is important to improve our service!</p>
        <form id="feedback" action="">
            <input type="hidden" name="page_id" id="id" value="<?php echo "$page_id"; ?>">
            <label for="name">Your name:</label>
            <input type="text" name="name" id="name" required placeholder="El Mahdi ...">
            <label for="email">Your E-mail:</label>
            <input type="email" name="email" id="email" required placeholder="test@example.com">
            <label for="desc">Description:</label>
            <textarea name="desc" id="desc" rows="5" required placeholder="Enter your message here..."></textarea>
            <input type="submit" class="form-control success" value="Send">
        </form>
    </div>

<?php }
    public function load_script()
    { ?>
        <script>
            let nonce = '<?php echo wp_create_nonce('wp_rest'); ?>';
            jQuery(feedback).submit(function(event) {
                event.preventDefault();
                let form = jQuery(this).serialize();
                jQuery.ajax({
                    method: 'post',
                    url: '<?php echo get_rest_url(null, 'feedback/send-email') ?>',
                    headers: {
                        'X-WP-Nonce': nonce
                    },
                    data: form
                })
                alert('Your feedback has been sent successfully');
                jQuery('#name, #email, #desc').val('');
            });
        </script>
<?php }
    public function register_rest_api()
    {
        register_rest_route('feedback', 'send-email', array(
            'methods' => 'POST',
            'callback' => array($this, 'handel_feedback')
        ));
    }
    public function handel_feedback($data)
    {
        $headers = $data->get_headers();
        $params = $data->get_params();
        $nonce = $headers['x_wp_nonce'][0];
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new WP_REST_Response('Message not sent', 422);
        }
        $post_id = wp_insert_post([
            'post_type' => 'Feedback_Form',
            'post_title' => $params['name'],
            'post_status' => 'publish'
        ]);

        foreach ($params as $label => $value) {
            add_post_meta($post_id, $label, $value);
        }

        if ($post_id) {
            return new WP_REST_Response('Thank you for your feedback', 200);
        }
    }
}

new Feedback;
