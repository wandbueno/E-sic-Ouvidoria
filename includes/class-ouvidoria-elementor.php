<?php
if (!defined('ABSPATH')) {
    exit;
}

class Ouvidoria_Elementor {
    public function __construct() {
        add_action('elementor/widgets/register', array($this, 'register_widgets'));
    }

    public function register_widgets($widgets_manager) {
        require_once OUVIDORIA_PLUGIN_DIR . 'includes/widgets/class-ouvidoria-form-widget.php';
        require_once OUVIDORIA_PLUGIN_DIR . 'includes/widgets/class-ouvidoria-consulta-widget.php';

        $widgets_manager->register(new \Ouvidoria_Form_Widget());
        $widgets_manager->register(new \Ouvidoria_Consulta_Widget());
    }
}

// Widget do Formulário
class Ouvidoria_Form_Widget extends \Elementor\Widget_Base {
    public function get_name() {
        return 'ouvidoria_form';
    }

    public function get_title() {
        return 'Formulário de Ouvidoria';
    }

    public function get_icon() {
        return 'eicon-form-horizontal';
    }

    public function get_categories() {
        return ['general'];
    }

    protected function render() {
        include OUVIDORIA_PLUGIN_DIR . 'public/views/form-ouvidoria.php';
    }
}

// Widget de Consulta
class Ouvidoria_Consulta_Widget extends \Elementor\Widget_Base {
    public function get_name() {
        return 'ouvidoria_consulta';
    }

    public function get_title() {
        return 'Consulta de Protocolo';
    }

    public function get_icon() {
        return 'eicon-search';
    }

    public function get_categories() {
        return ['general'];
    }

    protected function render() {
        include OUVIDORIA_PLUGIN_DIR . 'public/views/consulta-protocolo.php';
    }
}