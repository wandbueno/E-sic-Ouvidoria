<?php
if (!defined('ABSPATH')) {
    exit;
}

class Ouvidoria_Database {
    private $wpdb;
    private $table_solicitacoes;
    private $table_respostas;
    private $charset_collate;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_solicitacoes = $wpdb->prefix . 'ouvidoria_solicitacoes';
        $this->table_respostas = $wpdb->prefix . 'ouvidoria_respostas';
        $this->charset_collate = $wpdb->get_charset_collate();
    }
	
	public function get_solicitacao_by_protocolo($protocolo) {
        return $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->table_solicitacoes} WHERE protocolo = %s",
                $protocolo
            )
        );
    }

    public function verificar_tabelas() {
        $tabela_solicitacoes = $this->wpdb->get_var("SHOW TABLES LIKE '{$this->table_solicitacoes}'");
        $tabela_respostas = $this->wpdb->get_var("SHOW TABLES LIKE '{$this->table_respostas}'");
        
        if (!$tabela_solicitacoes || !$tabela_respostas) {
            error_log('Tabelas não encontradas. Tentando criar...');
            $this->create_tables();
            
            $tabela_solicitacoes = $this->wpdb->get_var("SHOW TABLES LIKE '{$this->table_solicitacoes}'");
            $tabela_respostas = $this->wpdb->get_var("SHOW TABLES LIKE '{$this->table_respostas}'");
        }
        
        return ($tabela_solicitacoes && $tabela_respostas);
    }

    public function create_tables() {
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        error_log('=== INÍCIO CRIAÇÃO DAS TABELAS ===');

        // First, remove existing tables to ensure clean creation
        $this->wpdb->query("DROP TABLE IF EXISTS {$this->table_respostas}");
        $this->wpdb->query("DROP TABLE IF EXISTS {$this->table_solicitacoes}");

        $sql_solicitacoes = "CREATE TABLE {$this->table_solicitacoes} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            protocolo varchar(20) NOT NULL,
            data_criacao datetime DEFAULT CURRENT_TIMESTAMP,
            identificacao enum('identificado','anonimo') NOT NULL,
            nome varchar(100) NULL,
            email varchar(100) NULL,
            telefone varchar(20) NULL,
            tipo_manifestacao varchar(50) NOT NULL,
            tipo_resposta enum('sistema','presencial') DEFAULT 'sistema',
            mensagem text NOT NULL,
            arquivo bigint(20) NULL,
            status enum('pendente','em_analise','respondida','encerrado','indeferida') DEFAULT 'pendente',
            PRIMARY KEY  (id),
            KEY protocolo (protocolo)
        ) {$this->charset_collate};";

        $resultado_solicitacoes = $this->wpdb->query($sql_solicitacoes);
        
        if ($resultado_solicitacoes === false) {
            error_log('Erro ao criar tabela de solicitações: ' . $this->wpdb->last_error);
            return false;
        }

        $sql_respostas = "CREATE TABLE {$this->table_respostas} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            solicitacao_id bigint(20) NOT NULL,
            data_resposta datetime DEFAULT CURRENT_TIMESTAMP,
            resposta text NOT NULL,
            respondido_por bigint(20) NOT NULL,
            arquivo bigint(20) NULL,
            status varchar(50) NULL,
            PRIMARY KEY  (id),
            KEY solicitacao_id (solicitacao_id),
            CONSTRAINT fk_solicitacao FOREIGN KEY (solicitacao_id) 
            REFERENCES {$this->table_solicitacoes}(id) ON DELETE CASCADE
        ) {$this->charset_collate};";

        $resultado_respostas = $this->wpdb->query($sql_respostas);
        
        if ($resultado_respostas === false) {
            error_log('Erro ao criar tabela de respostas: ' . $this->wpdb->last_error);
            return false;
        }

        error_log('=== FIM CRIAÇÃO DAS TABELAS ===');
        return true;
    }

    public function inserir_solicitacao($dados) {
        error_log('=== INÍCIO INSERÇÃO DE SOLICITAÇÃO ===');
        error_log('Dados recebidos: ' . print_r($dados, true));
        
        if (!$this->verificar_tabelas()) {
            error_log('Erro: Tabelas não existem');
            return false;
        }
        
        // Set default tipo_resposta if not provided
        if (!isset($dados['tipo_resposta'])) {
            $dados['tipo_resposta'] = 'sistema';
        }
        
        // Campos obrigatórios
        $campos_obrigatorios = array(
            'protocolo',
            'identificacao',
            'tipo_manifestacao',
            'mensagem'
        );
        
        foreach ($campos_obrigatorios as $campo) {
            if (empty($dados[$campo])) {
                error_log("Campo obrigatório faltando: {$campo}");
                return false;
            }
        }
        
        // Valores padrão
        $dados['data_criacao'] = current_time('mysql');
        $dados['status'] = isset($dados['status']) ? $dados['status'] : 'pendente';
        
        // Validação específica para solicitações identificadas
        if ($dados['identificacao'] === 'identificado') {
            if (empty($dados['nome']) || empty($dados['email'])) {
                error_log('Campos nome e email são obrigatórios para solicitação identificada');
                return false;
            }
        } else {
            unset($dados['nome'], $dados['email'], $dados['telefone']);
        }
        
        // Remove campos vazios
        $dados = array_filter($dados, function($value) {
            return $value !== '' && $value !== null;
        });
        
        error_log('Dados processados para inserção: ' . print_r($dados, true));
        
        // Tenta inserir os dados
        $resultado = $this->wpdb->insert(
            $this->table_solicitacoes,
            $dados
        );
        
        if ($resultado === false) {
            error_log('Erro MySQL: ' . $this->wpdb->last_error);
            return false;
        }
        
        $insert_id = $this->wpdb->insert_id;
        error_log('Solicitação inserida com sucesso. ID: ' . $insert_id);
        
        return $insert_id;
    }

    public function get_solicitacoes($args = array()) {
        $defaults = array(
            'page' => 1,
            'per_page' => 20,
            'status' => '',
            'search' => '',
            'orderby' => 'data_criacao',
            'order' => 'DESC'
        );
    
        $args = wp_parse_args($args, $defaults);
        $offset = ($args['page'] - 1) * $args['per_page'];
        
        $where = array('1=1');
        $prepare_values = array();
        
        if (!empty($args['status'])) {
            $where[] = "status = %s";
            $prepare_values[] = $args['status'];
        }
        
        if (!empty($args['search'])) {
            $where[] = "(protocolo LIKE %s OR nome LIKE %s OR email LIKE %s)";
            $search_term = '%' . $this->wpdb->esc_like($args['search']) . '%';
            $prepare_values[] = $search_term;
            $prepare_values[] = $search_term;
            $prepare_values[] = $search_term;
        }
    
        $prepare_values[] = (int) $args['per_page'];
        $prepare_values[] = (int) $offset;
    
        $where_clause = implode(' AND ', $where);
        $query = "SELECT SQL_CALC_FOUND_ROWS * 
                  FROM {$this->table_solicitacoes} 
                  WHERE {$where_clause} 
                  ORDER BY {$args['orderby']} {$args['order']}
                  LIMIT %d OFFSET %d";
        
        $items = $this->wpdb->get_results(
            $this->wpdb->prepare($query, $prepare_values)
        );
    
        $total = $this->wpdb->get_var("SELECT FOUND_ROWS()");
        
        return array(
            'items' => $items,
            'total' => $total,
            'pages' => ceil($total / $args['per_page'])
        );
    }

    public function get_solicitacao($id) {
        if (empty($id)) {
            return false;
        }

        $solicitacao = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM {$this->table_solicitacoes} WHERE id = %d",
            $id
        ));

        if (!$solicitacao) {
            return false;
        }

        // Processa o arquivo anexo se existir
        if (!empty($solicitacao->arquivo)) {
            $solicitacao->arquivo = wp_get_attachment_url($solicitacao->arquivo);
        }

        return $solicitacao;
    }

    public function get_respostas($solicitacao_id) {
        $respostas = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT r.*, u.display_name as nome_usuario
             FROM {$this->table_respostas} r
             LEFT JOIN {$this->wpdb->users} u ON r.respondido_por = u.ID
             WHERE r.solicitacao_id = %d 
             ORDER BY r.data_resposta DESC",
            $solicitacao_id
        ));
        
        foreach ($respostas as $resposta) {
            $resposta->data_resposta_formatada = date('d/m/Y H:i', strtotime($resposta->data_resposta));
            $resposta->nome_respondente = $resposta->nome_usuario ?: 'Usuário';
        }
        
        return $respostas;
    }

    public function adicionar_resposta($dados) {
        error_log('=== INÍCIO ADICIONAR RESPOSTA ===');
        error_log('Dados recebidos: ' . print_r($dados, true));

        try {
            // Validação dos dados obrigatórios
            if (empty($dados['solicitacao_id']) || empty($dados['resposta'])) {
                error_log('Dados obrigatórios faltando');
                return false;
            }

            // Preparar dados para inserção
            $dados_insert = array(
                'solicitacao_id' => intval($dados['solicitacao_id']),
                'resposta' => wp_kses_post($dados['resposta']),
                'respondido_por' => intval($dados['respondido_por']),
                'data_resposta' => current_time('mysql')
            );

            // Definir formatos dos campos
            $formats = array(
                '%d', // solicitacao_id
                '%s', // resposta
                '%d', // respondido_por
                '%s'  // data_resposta
            );

            // Adicionar arquivo se existir
            if (!empty($dados['arquivo'])) {
                $dados_insert['arquivo'] = intval($dados['arquivo']);
                $formats[] = '%d';
            }

            error_log('Dados preparados para inserção: ' . print_r($dados_insert, true));
            error_log('Formatos: ' . print_r($formats, true));

            // Inserir resposta
            $resultado = $this->wpdb->insert(
                $this->table_respostas,
                $dados_insert,
                $formats
            );

            if ($resultado === false) {
                error_log('Erro ao inserir resposta: ' . $this->wpdb->last_error);
                throw new Exception('Erro ao inserir resposta no banco de dados: ' . $this->wpdb->last_error);
            }

            $resposta_id = $this->wpdb->insert_id;
            error_log('Resposta inserida com sucesso. ID: ' . $resposta_id);

            // Atualizar status da solicitação se fornecido
            if (!empty($dados['status'])) {
                $status_atualizado = $this->atualizar_status_solicitacao(
                    $dados['solicitacao_id'],
                    $dados['status']
                );

                if (!$status_atualizado) {
                    error_log('Erro ao atualizar status da solicitação');
                }
            }

            return $resposta_id;

        } catch (Exception $e) {
            error_log('Exceção ao adicionar resposta: ' . $e->getMessage());
            return false;
        }
    }

    public function atualizar_status_solicitacao($id, $novo_status) {
        return $this->wpdb->update(
            $this->table_solicitacoes,
            array('status' => $novo_status),
            array('id' => $id),
            array('%s'),
            array('%d')
        );
    }

    public function get_estatisticas() {
        $total = $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->table_solicitacoes}");
    
        $status = $this->wpdb->get_results("
            SELECT status, COUNT(*) as total
            FROM {$this->table_solicitacoes}
            GROUP BY status
        ", OBJECT_K);
    
        return array(
            'total' => (int)$total,
            'pendentes' => isset($status['pendente']) ? (int)$status['pendente']->total : 0,
            'em_analise' => isset($status['em_analise']) ? (int)$status['em_analise']->total : 0,
            'concluidas' => isset($status['encerrado']) ? (int)$status['encerrado']->total : 0,
            'indeferidas' => isset($status['indeferida']) ? (int)$status['indeferida']->total : 0,
            'por_tipo' => $this->wpdb->get_results("
                SELECT tipo_manifestacao, COUNT(*) as total
                FROM {$this->table_solicitacoes}
                GROUP BY tipo_manifestacao
            ", OBJECT_K),
            'por_identificacao' => $this->wpdb->get_results("
                SELECT identificacao, COUNT(*) as total
                FROM {$this->table_solicitacoes}
                GROUP BY identificacao
            ", OBJECT_K)
        );
    }
}