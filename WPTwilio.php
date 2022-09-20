<?php

/*
Plugin Name: WPTwilio SMS
Description: A wordpress plugin for sending bulk SMS to Twilio Messaging Lists
Version: 0.1.4
Author: Richard Martin
*/

require_once( plugin_dir_path( __FILE__ ) .'/twilio-lib/twilio-php-main/src/Twilio/autoload.php');
use Twilio\Rest\Client;

class WPTwilio {
    public $pluginName = "WPTwilio";

    public function displayWPTSettingsPage() {
        include_once "wptwilio-admin-settings-page.php";
    }

    public function addWPTAdminOption() {
        add_menu_page(
            "WPTWILIO SMS",
            "WP Twilio",
            "manage_options",
            $this->pluginName,
            [$this, "displayWPTSettingsPage"]
        );
    }

    /**
     * Registers and Defines the necessary fields we need.
     *  @since    0.1.0
     */
    public function WPTAdminSettingsSave() {
        register_setting(
            $this->pluginName,
            $this->pluginName,
            [$this, "pluginOptionsValidate"]
        );
        add_settings_section(
            "wptwilio_main",
            "Integration Settings",
            [$this, "wptwilioSectionText"],
            "wptwilio-settings-page"
        );
        add_settings_field(
            "api_sid",
            "API SID",
            [$this, "WPTSettingSid"],
            "wptwilio-settings-page",
            "wptwilio_main"
        );
        add_settings_field(
            "api_auth_token",
            "API AUTH TOKEN",
            [$this, "WPTSettingToken"],
            "wptwilio-settings-page",
            "wptwilio_main"
        );
        add_settings_field(
            "api_phone",
            "Twilio Phone Number",
            [$this, "WPTSettingPhone"],
            "wptwilio-settings-page",
            "wptwilio_main"
        );
        add_settings_field(
            "api_mssid",
            "Message Service SID",
            [$this, "WPTSettingMSSID"],
            "wptwilio-settings-page",
            "wptwilio_main"
        );
    }

    /**
     * Displays the settings sub header
     *  @since    0.1.0
     */
    public function wptwilioSectionText() {
        echo '';
    }

    /**
     * Renders the sid input field
     *  @since    0.1.0
     */
    public function WPTSettingSid()
    {
        $options = get_option($this->pluginName);
        echo "
            <input
                id='$this->pluginName[api_sid]'
                name='$this->pluginName[api_sid]'
                size='40'
                type='text'
                value='{$options['api_sid']}'
                placeholder='Enter your API SID here'
            />
        ";
    }

    /**
     * Renders the auth_token input field
     *
     */
    public function WPTSettingToken()
    {
        $options = get_option($this->pluginName);
        echo "
            <input
                id='$this->pluginName[api_auth_token]'
                name='$this->pluginName[api_auth_token]'
                size='40'
                type='password'
                value='{$options['api_auth_token']}'
                placeholder='Enter your API AUTH TOKEN here'
            />
        ";
    }

    /**
     * Renders the phone number input field
     *
     */
    public function WPTSettingPhone()
    {
        $options = get_option($this->pluginName);
        echo "
            <input
                id='$this->pluginName[api_phone]'
                name='$this->pluginName[api_phone]'
                size='40'
                type='text'
                value='{$options['api_phone']}'
                placeholder='Enter your Twilio Phone Number here'
            />
        ";
    }

     /**
     * Renders the messaging service SID input field
     *
     */
    public function WPTSettingMSSID()
    {
        $options = get_option($this->pluginName);
        echo "
            <input
                id='$this->pluginName[api_mssid]'
                name='$this->pluginName[api_mssid]'
                size='40'
                type='text'
                value='{$options['api_mssid']}'
                placeholder='Enter your Messaging Service SID here'
            />
        ";
    }

    /**
     * Sanitizes all input fields.
     *
     */
    public function pluginOptionsValidate($input)
    {
        $newinput["api_sid"] = trim($input["api_sid"]);
        $newinput["api_auth_token"] = trim($input["api_auth_token"]);
        $newinput["api_phone"] = trim($input["api_phone"]);
        $newinput["api_mssid"] = trim($input["api_mssid"]);
        return $newinput;
    }

     /**
     * Register the sms page for the admin area.
     *  @since    1.0.0
     */
    public function registerWPTSmsPage()
    {
        // Create our settings page as a submenu page.
        add_submenu_page(
            $this->pluginName,
            __("WPTwilio SMS PAGE", $this->pluginName . "-sms"), // page title
            __("SMS Console", $this->pluginName . "-sms"), // menu title
            "manage_options", // capability
            $this->pluginName . "-sms", // menu_slug
            [$this, "displayWPTSmsPage"] // callable function
        );
    }

    /**
     * Display the sms page - The page we are going to be sending message from.
     *  @since    1.0.0
     */
    public function displayWPTSmsPage()
    {
        include_once "wptwilio-admin-sms-page.php";
    }

    public function send_message()
    {
        if (!isset($_POST["send_sms_message"])) {
            return;
        }

        $to        = (isset($_POST["numbers"])) ? $_POST["numbers"] : "";
        $sender_id = (isset($_POST["sender"]))  ? $_POST["sender"]  : "";
        $message   = (isset($_POST["message"])) ? $_POST["message"] : "";

        //gets our api details from the database.
        $api_details = get_option($this->pluginName);
        if (is_array($api_details) and count($api_details) != 0) {
            $TWILIO_SID = $api_details["api_sid"];
            $TWILIO_TOKEN = $api_details["api_auth_token"];
            $TWILIO_PHONE = $api_details["api_phone"];
            $TWILIO_MSSID = $api_details["api_mssid"];
        }

        try {
            $client = new Client($TWILIO_SID, $TWILIO_TOKEN);
            $response = $client->messages->create(
                $TWILIO_PHONE,
                array(
                    "MessagingServiceSid" => $TWILIO_MSSID,
                    "body" => $message
                )
            );
            self::DisplaySuccess();
        } catch (Exception $e) {
            self::DisplayError($e->getMessage());
        }
    }

    /**
     * Designs for displaying Notices
     *
     * @since    1.0.0
     * @access   private
     * @var $message - String - The message we are displaying
     * @var $status   - Boolean - its either true or false
     */
    public static function adminNotice($message, $status = true) {
        $class =  ($status) ? "notice notice-success" : "notice notice-error";
        $message = __( $message, "sample-text-domain" );
        printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) ); 
    }

    /**
     * Displays Error Notices
     *
     * @since    1.0.0
     * @access   private
     */
    public static function DisplayError($message = "Aww!, there was an error.") {
        add_action( 'adminNotices', function() use($message) {
            self::adminNotice($message, false);
        });
    }

    /**
     * Displays Success Notices
     *
     * @since    1.0.0
     * @access   private
     */
    public static function DisplaySuccess($message = "Successful!") {
        add_action( 'adminNotices', function() use($message) {
            self::adminNotice($message, true);
        });
    }

    /*
    * Opens a log file for numbers
    */
    public function update_log() {
        $numList = fopen("msgList.txt", "w") or die("Unable to open file!");
    }

}

// Begin Endpoint triggers

//Receive SMS and trigger update to messaging list
function trigger_receive_sms() {
    echo header('content-type: text/xml');

    echo <<<RESPOND
    <?xml version="1.0" encoding="UTF-8"?>
    <Response>
        <Message>Ahoy from Wordpress</Message>
    </Response>
    RESPOND;
    die();
}

// Create a new wpt instance
$wptInstance = new WPTwilio();
// Add setting menu item
add_action("admin_menu", [$wptInstance , "addWPTAdminOption"]);
// Saves and update settings
add_action("admin_init", [$wptInstance , 'WPTAdminSettingsSave']);

// Hook our sms page
add_action("admin_menu", [$wptInstance , "registerWPTSmsPage"]);

// calls the sending function whenever we try sending messages.
add_action( 'admin_init', [$wptInstance , "send_message"] );

// Hook the sms recieve trigger function
add_action( 'rest_api_init', function () {
    register_rest_route( 'wptwilio/v1', '/receive_sms', array(
      'methods' => 'POST',
      'callback' => 'trigger_receive_sms',
      ) );
  } );