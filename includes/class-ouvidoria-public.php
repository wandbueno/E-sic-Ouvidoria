<?php
if (!defined('ABSPATH')) {
    exit;
}

class Ouvidoria_Public {
    private $database;
    private $pdf;

    public function __construct($database) {
        $this->database = $database;
        $this->pdf = new Ouvidoria_PDF($database);
        
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_submit_ouvidoria', array($this, 'handle_form_submission'));
        add_action('wp_ajax_nopriv_submit_ouvidoria', array($this, 'handle_form_submission'));
        add_action('wp_ajax_consultar_protocolo', array($this, 'handle_consulta'));
        add_action('wp_ajax_nopriv_consultar_protocolo', array($this, 'handle_consulta'));
        add_action('wp_ajax_gerar_pdf_protocolo', array($this, 'gerar_pdf_protocolo'));
        add_action('wp_ajax_nopriv_gerar_pdf_protocolo', array($this, 'gerar_pdf_protocolo'));
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
                wp_send_json_error('Protocolo não encontrado em nossa base de dados.');
                return;
            }

            // Buscar respostas da solicitação
            $respostas = $this->database->get_respostas($solicitacao->id);

            // Formatar status
            $status_labels = array(
                'pendente' => 'Pendente',
                'em_analise' => 'Em Análise',
                'respondida' => 'Respondida',
                'encerrada' => 'Encerrada',
                'indeferida' => 'Indeferida'
            );

            // Formatar tipo de manifestação
            $tipo_labels = array(
                'reclamacao' => 'Reclamação',
                'denuncia' => 'Denúncia',
                'sugestao' => 'Sugestão',
                'elogio' => 'Elogio',
                'informacao' => 'Acesso à Informação'
            );

            // Formatar dados para retorno
            $data = array(
                'protocolo' => $solicitacao->protocolo,
                'status' => isset($status_labels[$solicitacao->status]) ? $status_labels[$solicitacao->status] : ucfirst($solicitacao->status),
                'data' => wp_date('d/m/Y H:i', strtotime($solicitacao->data_criacao)),
                'tipo' => isset($tipo_labels[$solicitacao->tipo_manifestacao]) ? $tipo_labels[$solicitacao->tipo_manifestacao] : ucfirst($solicitacao->tipo_manifestacao),
                'identificacao' => $solicitacao->identificacao,
                'nome' => $solicitacao->identificacao === 'identificado' ? $solicitacao->nome : 'Anônimo',
                'email' => $solicitacao->email ?: '',
                'telefone' => $solicitacao->telefone ?: '',
                'mensagem' => nl2br(esc_html($solicitacao->mensagem)),
                'arquivo' => null,
                'respostas' => array()
            );

            // Adicionar URL do arquivo se existir
            if (!empty($solicitacao->arquivo)) {
                $arquivo = get_post($solicitacao->arquivo);
                if ($arquivo) {
                    $data['arquivo'] = array(
                        'url' => wp_get_attachment_url($solicitacao->arquivo),
                        'nome' => get_the_title($solicitacao->arquivo)
                    );
                }
            }

            // Adicionar respostas se existirem
            if (!empty($respostas)) {
                foreach ($respostas as $resp) {
                    $user_info = get_userdata($resp->respondido_por);
                    $resposta = array(
                        'data' => wp_date('d/m/Y H:i', strtotime($resp->data_resposta)),
                        'texto' => nl2br(wp_kses_post($resp->resposta)),
                        'respondente' => $user_info ? $user_info->display_name : 'Administrador',
                        'arquivo' => null
                    );

                    if (!empty($resp->arquivo)) {
                        $arquivo_resposta = get_post($resp->arquivo);
                        if ($arquivo_resposta) {
                            $resposta['arquivo'] = array(
                                'url' => wp_get_attachment_url($resp->arquivo),
                                'nome' => get_the_title($resp->arquivo)
                            );
                        }
                    }

                    $data['respostas'][] = $resposta;
                }
            }

            wp_send_json_success($data);

        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    public function gerar_pdf_protocolo() {
        try {
            check_ajax_referer('ouvidoria_public_nonce', 'nonce');

            $protocolo = sanitize_text_field($_GET['protocolo']);
            if (empty($protocolo)) {
                throw new Exception('Protocolo não informado');
            }

            $solicitacao = $this->database->get_solicitacao_by_protocolo($protocolo);
            if (!$solicitacao) {
                throw new Exception('Protocolo não encontrado');
            }

            // Gerar PDF usando a classe compartilhada
            $this->pdf->gerar_pdf($solicitacao, true);

        } catch (Exception $e) {
            wp_die($e->getMessage());
        }
    }

    private function gerar_protocolo() {
        $ano = date('Y');
        $numero = mt_rand(100000, 999999);
        return sprintf('%d%06d', $ano, $numero);
    }
}