<?php
/**
 * WP Custom Signatures Generate Class.
 */
if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

if (! class_exists('SignaturesGenerate')) {
    class SignaturesGenerate
    {
        public function __construct()
        {
            if (is_admin() && current_user_can('edit_posts')) {
                add_action('admin_menu', [$this, 'sig_add_admin_page']);
            }
        }

        public function sig_add_admin_page()
        {
            add_menu_page(
                'Generate Signatures',
                'Signatures',
                'read',
                'generate-signatures',
                [$this, 'signature_render_settings_page'],
                'dashicons-id',
                50
            );
        }

        private function sig_replace_string($replace, $val, $output)
        {
            if (! is_string($val)) {
                return $output;
            } else {
                return str_replace($replace, $val, $output);
            }
        }

        /**
         * Allow Kerning to Font
         */
        private function imagettftextSp($image, $size, $angle, $x, $y, $color, $font, $text)
        {
            $temp_x  = $x;
            $spacing = 0;
            for ($i = 0; $i < strlen($text); $i++) {
                if ('V' == $text[$i]) {
                    $spacing = -3;
                } elseif ('A' == $text[$i]) {
                    $spacing = -1;
                } else {
                    $spacing = 0;
                }
                $bbox   = imagettftext($image, $size, $angle, $temp_x, $y, $color, $font, $text[$i]);
                $temp_x = $bbox[4] + $spacing;
            }
        }

        public function signature_render_settings_page()
        {
            ?>
<div class="wrap">
    <h2><?php echo esc_html(get_admin_page_title()); ?></h2>
    <form method="POST" action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>">
        <div class="form-wrap" style="max-width: 500px;">
            <div class="form-field">
                <input name="full_name" placeholder="Full Name" required type="text" value="" />
            </div>
            <div class="form-field">
                <input name="title" placeholder="Position or Title" required type="text" value="" />
            </div>
            <div class="form-field">
                <input name="email_address" placeholder="Email Address" required type="email" value="" />
            </div>
            <div class="form-field">
                <input name="company_name" placeholder="Company Name" required type="text" value="" />
            </div>
            <div class="form-field">
                <input name="company_address" placeholder="Company Address" required type="text" value="" />
            </div>
            <div class="form-field">
                <input name="company_city" placeholder="Company City" required type="text" value="" />
            </div>
            <div class="form-field">
                <input name="company_zip" placeholder="Company Zipcode" required type="text" value="" />
            </div>
            <div class="form-field">
                <input name="company_phone" placeholder="Company Phone" required type="text" value="" />
            </div>
            <div class="form-field">
                <input name="company_website" placeholder="Company Website" required type="text" value="" />
            </div>
        </div>
        <?php submit_button('Create Signature', 'primary', 'submit'); ?>
    </form>
    <hr>
    <div>
        <?php
if ('POST' === $_SERVER['REQUEST_METHOD'] && isset($_POST['submit'])) {
    $postArray = [
        'NAME'             => sanitize_text_field(strtoupper($_POST['full_name'])),
        'EMAIL'            => sanitize_email($_POST['email_address']),
        'COMPANY_NAME'     => sanitize_text_field($_POST['company_name']),
        'COMPANY_ADDRESS'  => sanitize_text_field($_POST['company_address']),
        'COMPANY_CITY'     => sanitize_text_field($_POST['company_city']),
        'COMPANY_ZIP'      => sanitize_text_field($_POST['company_zip']),
        'COMPANY_PHONE'    => sanitize_text_field($_POST['company_phone']),
        'COMPANY_WEBSITE'  => sanitize_text_field($_POST['companywebsite']),
        'IMAGE'            => 'signature_' . sanitize_title($_POST['full_name']) . '.png',
    ];
    $template            = plugin_dir_path(__FILE__) . 'template/signature.html';
    $logoPath            = plugin_dir_path(__FILE__) . 'images/logo.png';
    $signatureImagePath  = plugin_dir_path(__FILE__) . 'signatures/assets/signature_' . sanitize_title($postArray['NAME']) . '.png';
    $fontPath            = plugin_dir_path(__FILE__) . 'fonts/ITCAvantGardePro-Bold.otf';
    $fontPath2           = plugin_dir_path(__FILE__) . 'fonts/ITCAvantGardePro-Medium.otf';

    $parts  = explode('@', $postArray['EMAIL']);
    $width  = 752;
    $height = 106;
    $image  = imagecreatetruecolor($width, $height);
    // Create a BLock for Background Color
    $backgroundColor = imagecolorallocate($image, 255, 255, 255);
    imagefill($image, 0, 0, $backgroundColor);
    // Convert Image to PNG
    $logoImage = imagecreatefrompng($logoPath);
    // Create a Block for TEXT
    $textColor  = imagecolorallocate($image, 0, 0, 0);
    $textColor2 = imagecolorallocate($image, 255, 84, 3);

    $fontSize        = 31;
    $fontSize2       = 17;
    $fontSizeOffset  = 55;
    $fontSizeOffset2 = 85;
    $this->imagettftextSp($image, $fontSize, 0, 140, $fontSizeOffset, $textColor, $fontPath, $postArray['NAME']);
    imagettftext($image, $fontSize2, 0, 140, $fontSizeOffset2, $textColor2, $fontPath2, $postArray['TITLE']);

    imagecopy($image, $logoImage, 0, 0, 0, 0, imagesx($logoImage), imagesy($logoImage));
    imagepng($image, $signatureImagePath, 0);
    imagedestroy($image);
    imagedestroy($logoImage);

    if (file_exists($signatureImagePath)) {
        // Read HTML Template
        $output             = file_get_contents($template);
        foreach ($postArray as $key => $val) {
            $replace = '{{' . $key . '}}';
            $output  = $this->sig_replace_string($replace, $val, $output);
        }
        if (! empty($output)) {
            // Move new signature to directory
            file_put_contents(plugin_dir_path(__FILE__) . 'signatures/' . $parts[0] . '.html', $output);
            ?>
        <div style="margin-top: 2rem;">
            <a class="button button-primary" style="margin-right: 1rem;" href="download.php?id=<?php echo plugin_dir_url(__FILE__) . 'signatures/' . esc_attr($parts[0]); ?>" target="_blank">Download File</a>
            <a class="button button-primary" href="<?php echo plugin_dir_url(__FILE__) . 'signatures/' . esc_attr($parts[0]); ?>.html" target="_blank">Preview</a>
        </div>
        <?php
        }
    }
}
            ?>
    </div>
    <?php
        }
    }
}
?>