<?php
if (!defined('ABSPATH')) {
    exit;
}

class Ouvidoria_Admin {
    private $database;
    private $pdf;

    public function __construct($database) {
        $this->database = $database;
        $this->pdf = new Ouvidoria_PDF($database);
        
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_salvar_solicitacao', array($this, 'salvar_solicitacao'));
        add_action('wp_ajax_nopriv_salvar_solicitacao', array($this, 'salvar_solicitacao'));
        add_action('wp_ajax_responder_solicitacao', array($this, 'responder_solicitacao'));
        add_action('wp_ajax_adicionar_resposta', array($this, 'adicionar_resposta'));
        add_action('wp_ajax_gerar_pdf_solicitacao', array($this, 'gerar_pdf_solicitacao'));
    }

    public function enqueue_admin_scripts($hook) {
        // Debug
        error_log('Hook atual: ' . $hook);
        
        // Carregar em todas as páginas do plugin
        if (strpos($hook, 'ouvidoria') !== false) {
            // CSS principal
            wp_enqueue_style(
                'ouvidoria-admin-style', 
                OUVIDORIA_PLUGIN_URL . 'admin/css/admin.css',
                array(),
                OUVIDORIA_VERSION
            );
            
            // CSS específico para visualização
            if ($hook === 'admin_page_ouvidoria-visualizar') {
                wp_enqueue_style(
                    'ouvidoria-visualizar-style', 
                    OUVIDORIA_PLUGIN_URL . 'admin/css/visualizar-solicitacao.css',
                    array(),
                    OUVIDORIA_VERSION
                );
            }
            
            // JavaScript
            wp_enqueue_script(
                'ouvidoria-admin-script',
                OUVIDORIA_PLUGIN_URL . 'admin/js/admin.js',
                array('jquery'),
                OUVIDORIA_VERSION,
                true
            );
    
            wp_localize_script('ouvidoria-admin-script', 'ouvidoriaAdmin', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('ouvidoria_admin_nonce')
            ));
        }
    }

    public function add_admin_menu() {
        add_menu_page(
            'Ouvidoria',
            'Ouvidoria',
            'manage_options',
            'ouvidoria',
            array($this, 'render_admin_page'),
            'dashicons-feedback',
            30
        );

        add_submenu_page(
            'ouvidoria',
            'Todas as Solicitações',
            'Solicitações',
            'manage_options',
            'ouvidoria',
            array($this, 'render_admin_page')
        );

        add_submenu_page(
            'ouvidoria',
            'Nova Solicitação',
            'Adicionar Nova',
            'manage_options',
            'ouvidoria-nova',
            array($this, 'render_nova_solicitacao')
        );
		
		add_submenu_page(
			'ouvidoria',
			'Estatísticas Históricas',
			'Estatísticas Históricas',
			'manage_options',
			'ouvidoria-estatisticas-historicas',
			array($this, 'render_estatisticas_historicas')
		);

        // Página oculta para visualização (não aparece no menu)
        add_submenu_page(
            null,
            'Visualizar Solicitação',
            'Visualizar Solicitação',
            'manage_options',
            'ouvidoria-visualizar',
            array($this, 'render_visualizar_solicitacao')
        );
    }

    public function render_admin_page() {
        include OUVIDORIA_PLUGIN_DIR . 'admin/views/dashboard.php';
    }

    public function render_nova_solicitacao() {
        include OUVIDORIA_PLUGIN_DIR . 'admin/views/nova-solicitacao.php';
    }
	
	// E adicione também o método render_estatisticas_historicas logo após
	public function render_estatisticas_historicas() {
		include OUVIDORIA_PLUGIN_DIR . 'admin/views/estatisticas-historicas.php';
	}

public function adicionar_resposta() {
    try {
        // Verificar nonce
        if (!isset($_POST['resposta_nonce']) || !wp_verify_nonce($_POST['resposta_nonce'], 'ouvidoria_admin_nonce')) {
            throw new Exception('Verificação de segurança falhou');
        }

        // Verificar permissões
        if (!current_user_can('manage_options')) {
            throw new Exception('Permissão negada');
        }

        // Validar dados obrigatórios
        if (empty($_POST['solicitacao_id']) || empty($_POST['resposta'])) {
            throw new Exception('Campos obrigatórios não preenchidos');
        }

        // Preparar dados da resposta
        $dados_resposta = array(
            'solicitacao_id' => intval($_POST['solicitacao_id']),
            'resposta' => wp_kses_post($_POST['resposta']),
            'respondido_por' => get_current_user_id(),
            'data_resposta' => current_time('mysql')
        );

        // Se houver novo status, incluir
        if (!empty($_POST['novo_status'])) {
            $dados_resposta['status'] = sanitize_text_field($_POST['novo_status']);
        }

        // Processar upload de arquivo se houver
        if (!empty($_FILES['arquivo']) && $_FILES['arquivo']['error'] === 0) {
            if (!function_exists('wp_handle_upload')) {
                require_once(ABSPATH . 'wp-admin/includes/file.php');
            }

            $upload_overrides = array('test_form' => false);
            $arquivo_upload = wp_handle_upload($_FILES['arquivo'], $upload_overrides);

            if (isset($arquivo_upload['error'])) {
                throw new Exception('Erro no upload: ' . $arquivo_upload['error']);
            }

            // Criar anexo
            $anexo = array(
                'post_mime_type' => $arquivo_upload['type'],
                'post_title' => sanitize_file_name($_FILES['arquivo']['name']),
                'post_content' => '',
                'post_status' => 'inherit'
            );

            $attach_id = wp_insert_attachment($anexo, $arquivo_upload['file']);
            if (is_wp_error($attach_id)) {
                throw new Exception('Erro ao salvar anexo');
            }

            $dados_resposta['arquivo'] = $attach_id;
        }

        // Adicionar resposta
        $resultado = $this->database->adicionar_resposta($dados_resposta);
        if (!$resultado) {
            throw new Exception('Erro ao salvar resposta no banco de dados');
        }

        wp_send_json_success(array(
            'mensagem' => 'Resposta adicionada com sucesso!',
            'resposta_id' => $resultado
        ));

    } catch (Exception $e) {
        wp_send_json_error($e->getMessage());
    }
}




    public function render_visualizar_solicitacao() {
        include OUVIDORIA_PLUGIN_DIR . 'admin/views/visualizar-solicitacao.php';
    }

    public function salvar_solicitacao() {
        try {
            // Verify nonce for both admin and public forms
            if (isset($_POST['nova_nonce'])) {
                check_ajax_referer('ouvidoria_admin_nonce', 'nova_nonce');
            } elseif (isset($_POST['nonce'])) {
                check_ajax_referer('ouvidoria_public_nonce', 'nonce');
            } else {
                throw new Exception('Verificação de segurança falhou');
            }

            // Validate required fields
            if (empty($_POST['tipo_manifestacao']) || empty($_POST['identificacao']) || empty($_POST['mensagem'])) {
                throw new Exception('Campos obrigatórios não preenchidos');
            }

            // Prepare basic data
            $dados = array(
                'protocolo' => $this->gerar_protocolo(),
                'data_criacao' => current_time('mysql'),
                'identificacao' => sanitize_text_field($_POST['identificacao']),
                'tipo_manifestacao' => sanitize_text_field($_POST['tipo_manifestacao']),
				 'tipo_resposta' => sanitize_text_field($_POST['tipo_resposta']),
                'mensagem' => sanitize_textarea_field($_POST['mensagem']),
                'status' => 'pendente'
            );

            // Handle identified submissions
            if ($_POST['identificacao'] === 'identificado') {
                if (empty($_POST['nome']) || empty($_POST['email'])) {
                    throw new Exception('Nome e email são obrigatórios para manifestações identificadas');
                }
                $dados['nome'] = sanitize_text_field($_POST['nome']);
                $dados['email'] = sanitize_email($_POST['email']);
                if (!empty($_POST['telefone'])) {
                    $dados['telefone'] = sanitize_text_field($_POST['telefone']);
                }
            }

            // Handle file upload
            if (!empty($_FILES['arquivo']) && $_FILES['arquivo']['error'] === 0) {
                if (!function_exists('wp_handle_upload')) {
                    require_once(ABSPATH . 'wp-admin/includes/file.php');
                }
                
                $upload_overrides = array('test_form' => false);
                $uploaded_file = wp_handle_upload($_FILES['arquivo'], $upload_overrides);

                if (isset($uploaded_file['error'])) {
                    throw new Exception('Erro no upload: ' . $uploaded_file['error']);
                }

                // Create attachment
                $attachment = array(
                    'post_mime_type' => $uploaded_file['type'],
                    'post_title' => sanitize_file_name($_FILES['arquivo']['name']),
                    'post_content' => '',
                    'post_status' => 'inherit'
                );

                $attach_id = wp_insert_attachment($attachment, $uploaded_file['file']);
                if (is_wp_error($attach_id)) {
                    throw new Exception('Erro ao salvar arquivo');
                }

                $dados['arquivo'] = $attach_id;
            }

            // Save to database
            $resultado = $this->database->inserir_solicitacao($dados);
            if (!$resultado) {
                throw new Exception('Erro ao salvar solicitação no banco de dados');
            }

            // Generate HTML receipt
            $html_url = $this->gerar_comprovante_html($dados);

            wp_send_json_success(array(
                'mensagem' => 'Solicitação salva com sucesso!',
                'protocolo' => $dados['protocolo'],
                'comprovante_url' => $html_url
            ));

        } catch (Exception $e) {
            error_log('Erro ao salvar solicitação: ' . $e->getMessage());
            wp_send_json_error($e->getMessage());
        }
    }
	
	public function consultar_protocolo() {
		check_ajax_referer('ouvidoria_public_nonce', 'nonce');

		$protocolo = sanitize_text_field($_POST['protocolo']);
		$solicitacao = $this->database->get_solicitacao_by_protocolo($protocolo);

		if ($solicitacao) {
			$respostas = $this->database->get_respostas($solicitacao->id);

			wp_send_json_success(array(
				'status' => ucfirst($solicitacao->status),
				'data' => date('d/m/Y', strtotime($solicitacao->data_criacao)),
				'tipo' => ucfirst($solicitacao->tipo_manifestacao),
				'respostas' => array_map(function($resp) {
					return array(
						'data' => date('d/m/Y H:i', strtotime($resp->data_resposta)),
						'texto' => $resp->resposta
					);
				}, $respostas)
			));
		} else {
			wp_send_json_error('Protocolo não encontrado');
		}
	}

	public function get_estatisticas() {
		check_ajax_referer('ouvidoria_public_nonce', 'nonce');

		$stats = $this->database->get_estatisticas();
		wp_send_json_success($stats);
	}

    private function gerar_protocolo() {
        $ano = date('Y');
        $numero = mt_rand(100000, 999999);
        return sprintf('%d%06d', $ano, $numero);
    }

private function gerar_comprovante_html($dados) {
    $upload_dir = wp_upload_dir();
    $filename = 'protocolo-' . $dados['protocolo'] . '.html';
    $filepath = $upload_dir['path'] . '/' . $filename;
    $fileurl = $upload_dir['url'] . '/' . $filename;

    // Get attachment information if it exists
    $anexo_info = '';
    if (!empty($dados['arquivo'])) {
        $attachment = get_post($dados['arquivo']);
        if ($attachment) {
            $anexo_url = wp_get_attachment_url($dados['arquivo']);
            $anexo_info = '
            <div class="info-block">
                <h3>Anexo</h3>
                <p><strong>Nome do arquivo:</strong> ' . esc_html($attachment->post_title) . '</p>
                <p><a href="' . esc_url($anexo_url) . '" target="_blank">Baixar anexo</a></p>
            </div>';
        }
    }

    $html = '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Comprovante de Manifestação - ' . esc_html($dados['protocolo']) . '</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.4; margin: 20px; }
            .container { max-width: 800px; margin: 0 auto; }
            .header { text-align: center; margin-bottom: 20px; }
            .info-block { margin-bottom: 15px; padding: 12px; background: #f9f9f9; border-radius: 4px; }
            .info-block h3 { margin: 0 0 8px 0; color: #333; border-bottom: 1px solid #ddd; padding-bottom: 5px; }
            .info-block p { margin: 5px 0; }
            .footer { margin-top: 20px; text-align: center; font-size: 0.9em; }
            .print-button { 
                background: #0073aa; 
                color: white; 
                border: none; 
                padding: 8px 16px; 
                border-radius: 4px; 
                cursor: pointer;
                margin-bottom: 15px;
            }
            .print-button:hover { background: #005177; }
            @media print {
                .no-print { display: none; }
                .info-block { border: 1px solid #ddd; }
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <button class="print-button no-print" onclick="window.print()">Imprimir Comprovante</button>
                <h1>Comprovante de Manifestação</h1>
                <p>Data: ' . date('d/m/Y H:i', strtotime($dados['data_criacao'])) . '</p>
            </div>

            <div class="info-block">
                <h3>Informações da Manifestação</h3>
                <p><strong>Protocolo:</strong> ' . esc_html($dados['protocolo']) . '</p>
                <p><strong>Tipo:</strong> ' . esc_html(ucfirst($dados['tipo_manifestacao'])) . '</p>
                <p><strong>Status:</strong> Pendente</p>
                <p><strong>Identificação:</strong> ' . esc_html(ucfirst($dados['identificacao'])) . '</p>
            </div>

            ' . ($dados['identificacao'] === 'identificado' ? '
            <div class="info-block">
                <h3>Dados do Manifestante</h3>
                <p><strong>Nome:</strong> ' . esc_html($dados['nome']) . '</p>
                <p><strong>E-mail:</strong> ' . esc_html($dados['email']) . '</p>
                ' . (!empty($dados['telefone']) ? '<p><strong>Telefone:</strong> ' . esc_html($dados['telefone']) . '</p>' : '') . '
            </div>
            ' : '') . '

            <div class="info-block">
                <h3>Mensagem</h3>
                <p>' . nl2br(esc_html($dados['mensagem'])) . '</p>
            </div>

            ' . $anexo_info . '

            <div class="footer">
                <p><strong>IMPORTANTE:</strong> Guarde este número de protocolo para consultas futuras.</p>
            </div>
        </div>
    </body>
    </html>';

    file_put_contents($filepath, $html);
    return $fileurl;
}



    public function gerar_pdf_solicitacao() {
        try {
            // Verificações de segurança
            if (!isset($_GET['nonce']) || !wp_verify_nonce($_GET['nonce'], 'gerar_pdf_solicitacao')) {
                wp_die('Acesso negado');
            }

            if (!current_user_can('manage_options')) {
                wp_die('Permissão negada');
            }

            // Buscar dados
            $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
            if (!$id) {
                wp_die('ID da solicitação não fornecido');
            }

            $solicitacao = $this->database->get_solicitacao($id);
            if (!$solicitacao) {
                wp_die('Solicitação não encontrada');
            }

            // Gerar PDF usando a classe compartilhada
            $this->pdf->gerar_pdf($solicitacao, false);

        } catch (Exception $e) {
            wp_die('Erro ao gerar PDF: ' . $e->getMessage());
        }
    }
}