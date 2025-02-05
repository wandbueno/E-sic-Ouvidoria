<?php
/**
 * Plugin Name: Sistema de Ouvidoria
 * Description: Sistema integrado de ouvidoria com Elementor
 * Version: 1.0.0
 * Author: Wanderson Bueno
 */

if (!defined('ABSPATH')) {
    exit;
}

// Definições do Plugin
define('OUVIDORIA_VERSION', '1.0.0');
define('OUVIDORIA_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('OUVIDORIA_PLUGIN_URL', plugin_dir_url(__FILE__));


if (!defined('WP_CACHE')) {
    define('WP_CACHE', true);
}

// Incluir classes principais
require_once OUVIDORIA_PLUGIN_DIR . 'includes/class-ouvidoria-database.php';
require_once OUVIDORIA_PLUGIN_DIR . 'includes/class-ouvidoria-pdf.php';
require_once OUVIDORIA_PLUGIN_DIR . 'includes/class-ouvidoria-admin.php';
require_once OUVIDORIA_PLUGIN_DIR . 'includes/class-ouvidoria-public.php';
require_once plugin_dir_path(__FILE__) . 'shortcodes_cadastro.php';
require_once plugin_dir_path(__FILE__) . 'shortcodes_consulta.php';
require_once plugin_dir_path(__FILE__) . 'shortcodes_estatisticas.php';

class Sistema_Ouvidoria {
    private static $instance = null;
    public $admin;
    public $public;
    public $database;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
		date_default_timezone_set('America/Sao_Paulo');
        $this->init_hooks();
    }

    private function init_hooks() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        add_action('plugins_loaded', array($this, 'init'));
    }

    public function activate() {
        $database = new Ouvidoria_Database();
        $database->create_tables();
        flush_rewrite_rules();
    }

    public function deactivate() {
        flush_rewrite_rules();
    }

    public function init() {
        $this->database = new Ouvidoria_Database();
        $this->admin = new Ouvidoria_Admin($this->database);
        $this->public = new Ouvidoria_Public($this->database);
    }
}

function sistema_ouvidoria() {
    return Sistema_Ouvidoria::get_instance();
}

sistema_ouvidoria();