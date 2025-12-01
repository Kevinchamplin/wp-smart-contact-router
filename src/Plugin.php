<?php

declare(strict_types=1);

namespace KevinChamplin\WPSmartContactRouter;

final class Plugin
{
    private static ?Plugin $instance = null;

    public static function bootstrap(): void
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
    }

    private function __construct()
    {
        \add_action('init', [$this, 'registerShortcode']);
        \add_action('admin_post_scr_submit', [$this, 'handleSubmission']);
        \add_action('admin_post_nopriv_scr_submit', [$this, 'handleSubmission']);
    }

    public function registerShortcode(): void
    {
        \add_shortcode('smart_contact_router', [$this, 'renderShortcode']);
    }

    public function renderShortcode(array $atts = [], ?string $content = null, string $tag = ''): string
    {
        $topics = [
            'sales' => 'Sales',
            'support' => 'Support',
            'other' => 'Other',
        ];

        $topicOptions = '';

        foreach ($topics as $value => $label) {
            $topicOptions .= \sprintf(
                '<option value="%s">%s</option>',
                \esc_attr($value),
                \esc_html($label),
            );
        }

        $action = \esc_url(\admin_url('admin-post.php'));

        $nonceField = \wp_nonce_field('scr_submit', 'scr_nonce', true, false);

        return \sprintf(
            '<form method="post" action="%s" class="scr-form">'
            . '<div class="scr-row"><label>Name</label><input type="text" name="scr_name" required></div>'
            . '<div class="scr-row"><label>Email</label><input type="email" name="scr_email" required></div>'
            . '<div class="scr-row"><label>Topic</label><select name="scr_topic">%s</select></div>'
            . '<div class="scr-row"><label>Message</label><textarea name="scr_message" required></textarea></div>'
            . '<input type="hidden" name="action" value="scr_submit">%s'
            . '<button type="submit">Send</button>'
            . '</form>',
            $action,
            $topicOptions,
            $nonceField,
        );
    }

    public function handleSubmission(): void
    {
        if (! isset($_POST['scr_nonce']) || ! \wp_verify_nonce((string) $_POST['scr_nonce'], 'scr_submit')) {
            \wp_die('Invalid request.');
        }

        $name = isset($_POST['scr_name']) ? \sanitize_text_field((string) $_POST['scr_name']) : '';
        $email = isset($_POST['scr_email']) ? \sanitize_email((string) $_POST['scr_email']) : '';
        $topic = isset($_POST['scr_topic']) ? \sanitize_text_field((string) $_POST['scr_topic']) : '';
        $message = isset($_POST['scr_message']) ? \wp_kses_post((string) $_POST['scr_message']) : '';

        if ($name === '' || $email === '' || $message === '') {
            $this->redirectWithStatus('error');
        }

        $recipients = [
            'sales' => (string) \get_option('scr_email_sales', \get_option('admin_email')),
            'support' => (string) \get_option('scr_email_support', \get_option('admin_email')),
            'other' => (string) \get_option('scr_email_other', \get_option('admin_email')),
        ];

        $to = $recipients[$topic] ?? (string) \get_option('admin_email');

        $subject = \sprintf('[Contact - %s] %s', \ucfirst($topic ?: 'general'), $name);

        $headers = [];

        if ($email !== '') {
            $headers[] = 'Reply-To: ' . $email;
        }

        \wp_mail($to, $subject, $message, $headers);

        $this->redirectWithStatus('sent');
    }

    private function redirectWithStatus(string $status): void
    {
        $referer = \wp_get_referer();

        if (! $referer) {
            $referer = \home_url('/');
        }

        $location = \add_query_arg('scr_status', $status, $referer);

        \wp_safe_redirect($location);
        exit;
    }
}
