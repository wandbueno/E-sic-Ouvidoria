<?php
if (!defined('ABSPATH')) {
    exit;
}

class Ouvidoria_Public {
    private $database;

    public function __construct($database) {
        $this->database = $database;
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_submit_ouvidoria', array($this, 'handle_form_submission'));
        add_action('wp_ajax_nopriv_submit_ouvidoria', array($this, 'handle_form_submission'));
        add_action('wp_ajax_consultar_protocolo', array($this, 'handle_consulta'));
        add_action('wp_ajax_nopriv_consultar_protocolo', array($this, 'handle_consulta'));
        add_shortcode('ouvidoria_form', array($this, 'render_form'));
        add_shortcode('ouvidoria_consulta', array($this, 'render_consulta'));
    }

    public function enqueue_scripts() {
        wp_enqueue_style(
            'ouvidoria-public-style',
            OUVIDORIA_PLUGIN_URL . 'public/css/public.css',
            array(),
            OUVIDORIA_VERSION
        );

        wp_enqueue_script(
            'ouvidoria-public-script',
            OUVIDORIA_PLUGIN_URL . 'public/js/public.js',
            array('jquery'),
            OUVIDORIA_VERSION,
            true
        );

        wp_localize_script('ouvidoria-public-script', 'ouvidoriaPublic', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ouvidoria_public_nonce')
        ));
    }

    public function render_form() {
        ob_start();
        include OUVIDORIA_PLUGIN_DIR . 'public/views/form-ouvidoria.php';
        return ob_get_clean();
    }

    public function render_consulta() {
        ob_start();
        include OUVIDORIA_PLUGIN_DIR . 'public/views/consulta-protocolo.php';
        return ob_get_clean();
    }

    public function handle_form_submission() {
        check_ajax_referer('ouvidoria_public_nonce', 'nonce');

        // Processar o envio do formulário
        $response = array(
            'success' => true,
            'data' => array(
                'protocolo' => $this->gerar_protocolo()
            )
        );

        wp_send_json($response);
    }

    public function handle_consulta() {
        try {
            check_ajax_referer('ouvidoria_public_nonce', 'nonce');

            $protocolo = sanitize_text_field($_POST['protocolo']);
            
            if (empty($protocolo)) {
                throw new Exception('Protocolo não informado');
            }

            // Buscar solicitação no banco de dados
            $solicitacao = $this->database->get_solicitacao_by_protocolo($protocolo);
            
            if (!$solicitacao) {
                wp_send_json_error('Protocolo não encontrado');
                return;
            }

            // Buscar respostas da solicitação
            $respostas = $this->database->get_respostas($solicitacao->id);

            // Formatar dados para retorno
            $data = array(
                'protocolo' => $solicitacao->protocolo,
                'status' => ucfirst($solicitacao->status),
                'data' => date('d/m/Y', strtotime($solicitacao->data_criacao)),
                'tipo' => ucfirst($solicitacao->tipo_manifestacao),
                'respostas' => array()
            );

            // Adicionar respostas se existirem
            if (!empty($respostas)) {
                foreach ($respostas as $resp) {
                    $data['respostas'][] = array(
                        'data' => date('d/m/Y H:i', strtotime($resp->data_resposta)),
                        'texto' => wp_strip_all_tags($resp->resposta)
                    );
                }
            }

            wp_send_json_success($data);

        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    private function gerar_protocolo() {
        $ano = date('Y');
        $numero = mt_rand(100000, 999999);
        return sprintf('%d%06d', $ano, $numero);
    }
}