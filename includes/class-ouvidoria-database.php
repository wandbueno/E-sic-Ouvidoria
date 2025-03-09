<?php
if (!defined('ABSPATH')) {
    exit;
}

class Ouvidoria_Database {
    private $wpdb;
    private $table_solicitacoes;
    private $table_respostas;
    private $table_estatisticas_historicas;
    private $charset_collate;
	
    public function __construct() {
		global $wpdb;
		$this->wpdb = $wpdb;
		// Usar o prefixo correto do WordPress
		$this->table_solicitacoes = $this->wpdb->prefix . 'ouvidoria_solicitacoes';
		$this->table_respostas = $this->wpdb->prefix . 'ouvidoria_respostas';
		$this->table_estatisticas_historicas = $this->wpdb->prefix . 'ouvidoria_estatisticas_historicas';
		$this->charset_collate = $this->wpdb->get_charset_collate();
	
		 // Configurar timezone do MySQL
    	$this->wpdb->query("SET time_zone = '-03:00'");
		
		 // Verifica e atualiza a estrutura da tabela quando necessário
         add_action('admin_init', array($this, 'verificar_atualizacoes_tabela'));
	}
	
	public function verificar_atualizacoes_tabela() {
        $versao_atual = get_option('ouvidoria_db_version', '1.0');
        
        if (version_compare($versao_atual, '1.1', '<')) {
            $this->atualizar_tabela_v1_1();
            update_option('ouvidoria_db_version', '1.1');
        }
    }
	
	  private function atualizar_tabela_v1_1() {
        error_log('Iniciando atualização da tabela para versão 1.1');
        
        try {
            // Verificar se a tabela existe
            $tabela_existe = $this->wpdb->get_var("SHOW TABLES LIKE '{$this->table_solicitacoes}'");
            if (!$tabela_existe) {
                error_log('Tabela de solicitações não existe');
                return false;
            }

            // Verificar e adicionar coluna cpf_cnpj
            $coluna_cpf_cnpj = $this->wpdb->get_results("SHOW COLUMNS FROM {$this->table_solicitacoes} LIKE 'cpf_cnpj'");
            if (empty($coluna_cpf_cnpj)) {
                error_log('Adicionando coluna cpf_cnpj');
                $this->wpdb->query("ALTER TABLE {$this->table_solicitacoes} 
                                  ADD COLUMN cpf_cnpj varchar(20) NULL 
                                  AFTER email");
                
                if ($this->wpdb->last_error) {
                    error_log('Erro ao adicionar coluna cpf_cnpj: ' . $this->wpdb->last_error);
                    return false;
                }
            }

            // Verificar e adicionar coluna endereco
            $coluna_endereco = $this->wpdb->get_results("SHOW COLUMNS FROM {$this->table_solicitacoes} LIKE 'endereco'");
            if (empty($coluna_endereco)) {
                error_log('Adicionando coluna endereco');
                $this->wpdb->query("ALTER TABLE {$this->table_solicitacoes} 
                                  ADD COLUMN endereco text NULL 
                                  AFTER cpf_cnpj");
                
                if ($this->wpdb->last_error) {
                    error_log('Erro ao adicionar coluna endereco: ' . $this->wpdb->last_error);
                    return false;
                }
            }

            error_log('Atualização da tabela concluída com sucesso');
            return true;

        } catch (Exception $e) {
            error_log('Erro durante a atualização da tabela: ' . $e->getMessage());
            return false;
        }
    }
	
   public function verificar_tabelas() {
    $tabela_solicitacoes = $this->wpdb->get_var("SHOW TABLES LIKE '{$this->table_solicitacoes}'");
    $tabela_respostas = $this->wpdb->get_var("SHOW TABLES LIKE '{$this->table_respostas}'");
    $tabela_estatisticas = $this->wpdb->get_var("SHOW TABLES LIKE '{$this->table_estatisticas_historicas}'");
    
    $precisa_criar = false;
    
    if (!$tabela_solicitacoes) {
        error_log('Tabela de solicitações não encontrada');
        $precisa_criar = true;
    }
    
    if (!$tabela_respostas) {
        error_log('Tabela de respostas não encontrada');
        $precisa_criar = true;
    }
    
    if (!$tabela_estatisticas) {
        error_log('Tabela de estatísticas não encontrada');
        $precisa_criar = true;
    }
    
    if ($precisa_criar) {
        error_log('Tentando criar tabelas faltantes...');
        $this->create_tables();
        
        // Verificar novamente
        $tabela_solicitacoes = $this->wpdb->get_var("SHOW TABLES LIKE '{$this->table_solicitacoes}'");
        $tabela_respostas = $this->wpdb->get_var("SHOW TABLES LIKE '{$this->table_respostas}'");
        $tabela_estatisticas = $this->wpdb->get_var("SHOW TABLES LIKE '{$this->table_estatisticas_historicas}'");
    }
    
    return ($tabela_solicitacoes && $tabela_respostas && $tabela_estatisticas);
}


public function create_tables() {
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    error_log('=== INÍCIO VERIFICAÇÃO/CRIAÇÃO DAS TABELAS ===');

    // Criar tabela de solicitações se não existir
    $sql_solicitacoes = "CREATE TABLE IF NOT EXISTS {$this->table_solicitacoes} (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        protocolo varchar(20) NOT NULL,
        data_criacao datetime DEFAULT CURRENT_TIMESTAMP,
        identificacao enum('identificado','anonimo') NOT NULL,
        nome varchar(100) NULL,
        email varchar(100) NULL,
        cpf_cnpj varchar(20) NULL,
        endereco text NULL,
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
        error_log('Erro ao verificar/criar tabela de solicitações: ' . $this->wpdb->last_error);
        return false;
    }

    // Criar tabela de respostas se não existir
    $sql_respostas = "CREATE TABLE IF NOT EXISTS {$this->table_respostas} (
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
        error_log('Erro ao verificar/criar tabela de respostas: ' . $this->wpdb->last_error);
        return false;
    }

    // Criar tabela de estatísticas se não existir
    $sql_estatisticas = "CREATE TABLE IF NOT EXISTS {$this->table_estatisticas_historicas} (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        ano int(4) NOT NULL,
        mes int(2) NOT NULL,
        total_manifestacoes int NOT NULL DEFAULT 0,
        reclamacoes int NOT NULL DEFAULT 0,
        denuncias int NOT NULL DEFAULT 0,
        sugestoes int NOT NULL DEFAULT 0,
        elogios int NOT NULL DEFAULT 0,
        informacoes int NOT NULL DEFAULT 0,
        identificadas int NOT NULL DEFAULT 0,
        anonimas int NOT NULL DEFAULT 0,
        pendentes int NOT NULL DEFAULT 0,
        em_analise int NOT NULL DEFAULT 0,
        respondidas int NOT NULL DEFAULT 0,
        encerradas int NOT NULL DEFAULT 0,
        indeferidas int NOT NULL DEFAULT 0,
        resposta_sistema int NOT NULL DEFAULT 0,
        resposta_presencial int NOT NULL DEFAULT 0,
        observacoes text NULL,
        data_registro datetime DEFAULT CURRENT_TIMESTAMP,
        registrado_por bigint(20) NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY ano_mes (ano, mes)
    ) {$this->charset_collate};";

    $resultado_estatisticas = $this->wpdb->query($sql_estatisticas);
    
    if ($resultado_estatisticas === false) {
        error_log('Erro ao verificar/criar tabela de estatísticas: ' . $this->wpdb->last_error);
        return false;
    }

    // Verificar e adicionar colunas se necessário
    $this->verificar_atualizacoes_tabela();

    error_log('=== FIM VERIFICAÇÃO/CRIAÇÃO DAS TABELAS ===');
    return true;
}



    public function inserir_estatistica_historica($dados) {
        error_log('=== INÍCIO INSERÇÃO DE ESTATÍSTICA HISTÓRICA ===');
        error_log('Dados recebidos: ' . print_r($dados, true));

        try {
            // Verificar se já existe registro para este mês/ano
            $existe = $this->wpdb->get_var(
                $this->wpdb->prepare(
                    "SELECT id FROM {$this->table_estatisticas_historicas} 
                     WHERE ano = %d AND mes = %d",
                    $dados['ano'],
                    $dados['mes']
                )
            );

            if ($existe) {
                error_log('Já existe registro para ' . $dados['ano'] . '/' . $dados['mes']);
                return false;
            }

            // Verificar se a tabela existe
            if (!$this->verificar_tabelas()) {
                error_log('Tabela de estatísticas não existe');
                return false;
            }

            // Preparar dados para inserção
            $dados_insert = array(
                'ano' => intval($dados['ano']),
                'mes' => intval($dados['mes']),
                'total_manifestacoes' => intval($dados['total_manifestacoes']),
                'reclamacoes' => intval($dados['reclamacoes']),
                'denuncias' => intval($dados['denuncias']),
                'sugestoes' => intval($dados['sugestoes']),
                'elogios' => intval($dados['elogios']),
                'informacoes' => intval($dados['informacoes']),
                'identificadas' => intval($dados['identificadas']),
                'anonimas' => intval($dados['anonimas']),
                'pendentes' => intval($dados['pendentes']),
                'em_analise' => intval($dados['em_analise']),
                'respondidas' => intval($dados['respondidas']),
                'encerradas' => intval($dados['encerradas']),
                'indeferidas' => intval($dados['indeferidas']),
                'resposta_sistema' => intval($dados['resposta_sistema']),
                'resposta_presencial' => intval($dados['resposta_presencial']),
                'observacoes' => sanitize_textarea_field($dados['observacoes']),
                'data_registro' => current_time('mysql'),
                'registrado_por' => get_current_user_id()
            );

            // Definir formatos dos campos
            $formats = array(
                '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d',
                '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%s', '%s', '%d'
            );

            error_log('Tentando inserir dados: ' . print_r($dados_insert, true));

            // Inserir dados
            $resultado = $this->wpdb->insert(
                $this->table_estatisticas_historicas,
                $dados_insert,
                $formats
            );

            if ($resultado === false) {
                error_log('Erro ao inserir estatísticas: ' . $this->wpdb->last_error);
                throw new Exception('Erro ao inserir dados: ' . $this->wpdb->last_error);
            }

            $insert_id = $this->wpdb->insert_id;
            error_log('Estatística inserida com sucesso. ID: ' . $insert_id);

            return $insert_id;

        } catch (Exception $e) {
            error_log('Exceção ao inserir estatística: ' . $e->getMessage());
            return false;
        }
    }

    public function get_estatisticas_historicas($ano = null) {
        if ($ano) {
            return $this->wpdb->get_results(
                $this->wpdb->prepare(
                    "SELECT * FROM {$this->table_estatisticas_historicas} 
                     WHERE ano = %d ORDER BY mes ASC",
                    $ano
                )
            );
        }
        return $this->wpdb->get_results(
            "SELECT * FROM {$this->table_estatisticas_historicas} 
             ORDER BY ano DESC, mes DESC"
        );
    }

    public function get_solicitacao_by_protocolo($protocolo) {
        return $this->wpdb->get_row($this->wpdb->prepare(
			"SELECT id, protocolo, data_criacao, identificacao, nome, email, 
					cpf_cnpj, endereco, telefone, tipo_manifestacao, tipo_resposta, 
					mensagem, arquivo, status 
			 FROM {$this->table_solicitacoes} 
			 WHERE protocolo = %s",
			$protocolo
		));
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
        'tipo_resposta',
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
    
    // Preparar dados para inserção
    $dados_insert = array(
        'protocolo' => $dados['protocolo'],
        'data_criacao' => $dados['data_criacao'],
        'identificacao' => $dados['identificacao'],
        'tipo_manifestacao' => $dados['tipo_manifestacao'],
        'tipo_resposta' => $dados['tipo_resposta'],
        'mensagem' => $dados['mensagem'],
        'status' => $dados['status']
    );

    // Adicionar campos de identificação se necessário
    if ($dados['identificacao'] === 'identificado') {
        if (!empty($dados['nome'])) $dados_insert['nome'] = sanitize_text_field($dados['nome']);
        if (!empty($dados['email'])) $dados_insert['email'] = sanitize_email($dados['email']);
        if (!empty($dados['telefone'])) $dados_insert['telefone'] = sanitize_text_field($dados['telefone']);
        if (!empty($dados['cpf_cnpj'])) $dados_insert['cpf_cnpj'] = sanitize_text_field($dados['cpf_cnpj']);
        if (!empty($dados['endereco'])) $dados_insert['endereco'] = sanitize_textarea_field($dados['endereco']);
    }

    // Adicionar arquivo se existir
    if (!empty($dados['arquivo'])) {
        $dados_insert['arquivo'] = $dados['arquivo'];
    }
    
    error_log('Dados processados para inserção: ' . print_r($dados_insert, true));
    
    // Tenta inserir os dados
    $resultado = $this->wpdb->insert(
        $this->table_solicitacoes,
        $dados_insert
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
        "SELECT id, protocolo, data_criacao, identificacao, nome, email, 
                cpf_cnpj, endereco, telefone, tipo_manifestacao, tipo_resposta, 
                mensagem, arquivo, status 
         FROM {$this->table_solicitacoes} 
         WHERE id = %d",
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
        // Usar DISTINCT para evitar duplicatas e converter o timezone corretamente
         $respostas = $this->wpdb->get_results($this->wpdb->prepare(
			"SELECT DISTINCT r.*, u.display_name as nome_usuario
			 FROM {$this->table_respostas} r
			 LEFT JOIN {$this->wpdb->users} u ON r.respondido_por = u.ID
			 WHERE r.solicitacao_id = %d 
			 ORDER BY r.data_resposta DESC",
			$solicitacao_id
		));
        
         foreach ($respostas as $resposta) {
			$resposta->data_resposta_formatada = wp_date('d/m/Y H:i', strtotime($resposta->data_resposta));
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
            throw new Exception('Dados obrigatórios não preenchidos');
        }

        // Adicionar um lock para evitar inserções simultâneas
        $lock_name = 'resposta_lock_' . $dados['solicitacao_id'];
        $lock_result = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT GET_LOCK(%s, 10) as locked",
                $lock_name
            )
        );

        if (!$lock_result || !$lock_result->locked) {
            error_log('Não foi possível obter o lock para adicionar resposta');
            throw new Exception('Erro ao processar a resposta. Tente novamente.');
        }

        try {
            // Verificar se a resposta já existe (melhorada)
             $resposta_existente = $this->wpdb->get_var($this->wpdb->prepare(
				"SELECT id FROM {$this->table_respostas} 
				WHERE solicitacao_id = %d 
				AND resposta = %s 
				AND respondido_por = %d 
				AND data_resposta > DATE_SUB(NOW(), INTERVAL 5 SECOND)",
				intval($dados['solicitacao_id']),
				wp_kses_post($dados['resposta']),
				intval($dados['respondido_por'])
			));

             if ($resposta_existente) {
				error_log('Resposta duplicada detectada');
				return $resposta_existente;
			}
			
			 // Configurar timezone para Brasília
       		 $this->wpdb->query("SET time_zone = '-03:00'");
			
			// Usar current_time() do WordPress para pegar a data/hora correta
            $data_atual = current_time('mysql');

            // Preparar dados para inserção usando NOW() do MySQL
			$dados_insert = array(
                'solicitacao_id' => intval($dados['solicitacao_id']),
                'resposta' => wp_kses_post($dados['resposta']),
                'respondido_por' => intval($dados['respondido_por']),
                'data_resposta' => $data_atual
            );
			
			// Adicionar arquivo se existir
            if (!empty($dados['arquivo'])) {
                $dados_insert['arquivo'] = intval($dados['arquivo']);
            }
			
			error_log('Dados preparados para inserção: ' . print_r($dados_insert, true));
			
			// Inserir resposta
            $resultado = $this->wpdb->insert(
                $this->table_respostas,
                $dados_insert,
                array(
                    '%d', // solicitacao_id
                    '%s', // resposta
                    '%d', // respondido_por
                    '%s', // data_resposta
                    '%d'  // arquivo (se existir)
                )
            );

            if ($resultado === false) {
                error_log('Erro ao inserir resposta: ' . $this->wpdb->last_error);
                throw new Exception($this->wpdb->last_error ?: 'Erro desconhecido ao salvar resposta');
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
                    error_log('Aviso: Erro ao atualizar status da solicitação');
                }
            }

            return $resposta_id;

         } finally {
            // Liberar o lock sempre
            $this->wpdb->query($this->wpdb->prepare(
                "SELECT RELEASE_LOCK(%s)",
                $lock_name
            ));
        }

		} catch (Exception $e) {
			error_log('Exceção ao adicionar resposta: ' . $e->getMessage());
			throw $e;
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

    public function get_anos_disponiveis() {
        return $this->wpdb->get_col("
            SELECT DISTINCT YEAR(data_criacao) as ano
            FROM {$this->table_solicitacoes}
            ORDER BY ano DESC
        ");
    }

    public function get_anos_historicos() {
        return $this->wpdb->get_col("
            SELECT DISTINCT ano 
            FROM {$this->table_estatisticas_historicas}
            ORDER BY ano DESC
        ");
    }

    public function get_estatisticas($ano = null) {
        // Se o ano for nulo, usar o ano atual
        if ($ano === null) {
            $ano = date('Y');
        }

        // Caso especial para "Todas..." - retornar dados de todos os anos
        if ($ano === 'todos') {
            return $this->get_estatisticas_totais();
        }

        // Primeiro tentar buscar das estatísticas históricas
        $stats_historicas = $this->get_estatisticas_historicas($ano);
        
        if (!empty($stats_historicas)) {
            // Somar todos os meses para o ano
            $total = 0;
            $pendentes = 0;
            $em_analise = 0;
            $respondidas = 0;
            $encerradas = 0;
            $indeferidas = 0;
            $por_tipo = array();
            $por_identificacao = array();
            $por_tipo_resposta = array();
            $por_mes = array();

            foreach ($stats_historicas as $stat) {
                $total += $stat->total_manifestacoes;
                $pendentes += $stat->pendentes;
                $em_analise += $stat->em_analise;
                $respondidas += $stat->respondidas;
                $encerradas += $stat->encerradas;
                $indeferidas += $stat->indeferidas;

                // Acumular dados por tipo
				$mes_key = sprintf('%d-%02d', $stat->ano, $stat->mes);
                $por_mes[$mes_key] = (object)array('total' => $stat->total_manifestacoes);

                // Acumular tipos de manifestação
                if (!isset($por_tipo['reclamacao'])) $por_tipo['reclamacao'] = (object)array('total' => 0);
                if (!isset($por_tipo['denuncia'])) $por_tipo['denuncia'] = (object)array('total' => 0);
                if (!isset($por_tipo['sugestao'])) $por_tipo['sugestao'] = (object)array('total' => 0);
                if (!isset($por_tipo['elogio'])) $por_tipo['elogio'] = (object)array('total' => 0);
                if (!isset($por_tipo['informacao'])) $por_tipo['informacao'] = (object)array('total' => 0);

                $por_tipo['reclamacao']->total += $stat->reclamacoes;
                $por_tipo['denuncia']->total += $stat->denuncias;
                $por_tipo['sugestao']->total += $stat->sugestoes;
                $por_tipo['elogio']->total += $stat->elogios;
                $por_tipo['informacao']->total += $stat->informacoes;

                // Acumular identificação
                if (!isset($por_identificacao['identificado'])) $por_identificacao['identificado'] = (object)array('total' => 0);
                if (!isset($por_identificacao['anonimo'])) $por_identificacao['anonimo'] = (object)array('total' => 0);

                $por_identificacao['identificado']->total += $stat->identificadas;
                $por_identificacao['anonimo']->total += $stat->anonimas;

                // Acumular tipo de resposta
                if (!isset($por_tipo_resposta['sistema'])) $por_tipo_resposta['sistema'] = (object)array('total' => 0);
                if (!isset($por_tipo_resposta['presencial'])) $por_tipo_resposta['presencial'] = (object)array('total' => 0);

                $por_tipo_resposta['sistema']->total += $stat->resposta_sistema;
                $por_tipo_resposta['presencial']->total += $stat->resposta_presencial;
            }

            return array(
                'total' => $total,
                'pendentes' => $pendentes,
                'em_analise' => $em_analise,
                'concluidas' => $encerradas,
                'indeferidas' => $indeferidas,
                'por_tipo' => $por_tipo,
                'por_identificacao' => $por_identificacao,
                'por_tipo_resposta' => $por_tipo_resposta,
                'por_mes' => $por_mes
            );
        }

        // Se não houver dados históricos, buscar das solicitações atuais
        $where_ano = $this->wpdb->prepare("WHERE YEAR(data_criacao) = %d", $ano);

        // Total geral e status
        $total = $this->wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$this->table_solicitacoes}
            {$where_ano}
        ");

        $status = $this->wpdb->get_results("
            SELECT status, COUNT(*) as total
            FROM {$this->table_solicitacoes}
            {$where_ano}
            GROUP BY status
        ", OBJECT_K);

        // Estatísticas por tipo de resposta
        $tipo_resposta = $this->wpdb->get_results("
            SELECT tipo_resposta, COUNT(*) as total
            FROM {$this->table_solicitacoes}
            {$where_ano}
            GROUP BY tipo_resposta
        ", OBJECT_K);

        // Estatísticas por mês - Corrigido para usar o mês atual
        $por_mes = $this->wpdb->get_results("
            SELECT 
                DATE_FORMAT(data_criacao, '%Y-%m') as mes,
                COUNT(*) as total
            FROM {$this->table_solicitacoes}
            {$where_ano}
            GROUP BY mes
    		ORDER BY mes ASC
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
                {$where_ano}
                GROUP BY tipo_manifestacao
            ", OBJECT_K),
            'por_identificacao' => $this->wpdb->get_results("
                SELECT identificacao, COUNT(*) as total
                FROM {$this->table_solicitacoes}
                {$where_ano}
                GROUP BY identificacao
            ", OBJECT_K),
            'por_tipo_resposta' => $tipo_resposta,
            'por_mes' => $por_mes
        );
    }

    public function get_estatisticas_totais() {
        // Inicializar contadores
        $total = 0;
        $pendentes = 0;
        $em_analise = 0;
        $concluidas = 0;
        $indeferidas = 0;
        $sistema = 0;
        $presencial = 0;
        
        // Buscar dados das solicitações atuais
        $total_solicitacoes = $this->wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$this->table_solicitacoes}
        ");
        $total += (int)$total_solicitacoes;
        
        // Status nas solicitações atuais
        $status = $this->wpdb->get_results("
            SELECT status, COUNT(*) as total
            FROM {$this->table_solicitacoes}
            GROUP BY status
        ", OBJECT_K);
        
        // Acumular status
        $pendentes += isset($status['pendente']) ? (int)$status['pendente']->total : 0;
        $em_analise += isset($status['em_analise']) ? (int)$status['em_analise']->total : 0;
        $concluidas += isset($status['encerrado']) ? (int)$status['encerrado']->total : 0;
        $indeferidas += isset($status['indeferida']) ? (int)$status['indeferida']->total : 0;
        
        // Tipo de resposta nas solicitações atuais
        $tipo_resposta = $this->wpdb->get_results("
            SELECT tipo_resposta, COUNT(*) as total
            FROM {$this->table_solicitacoes}
            GROUP BY tipo_resposta
        ", OBJECT_K);
        
        // Acumular tipo de resposta
        $sistema += isset($tipo_resposta['sistema']) ? (int)$tipo_resposta['sistema']->total : 0;
        $presencial += isset($tipo_resposta['presencial']) ? (int)$tipo_resposta['presencial']->total : 0;

        // Estatísticas por mês (para gráfico por mês)
        $por_mes_query = $this->wpdb->get_results("
            SELECT 
                CONCAT(YEAR(data_criacao), '-', LPAD(MONTH(data_criacao), 2, '0')) as mes_ano,
                COUNT(*) as total
            FROM {$this->table_solicitacoes}
            GROUP BY mes_ano
            ORDER BY YEAR(data_criacao), MONTH(data_criacao)
        ");
        
        // Processar resultados para o formato esperado
        $por_mes = array();
        foreach ($por_mes_query as $item) {
            $por_mes[$item->mes_ano] = (object)array('total' => (int)$item->total);
        }

        // Dados por tipo de manifestação nas solicitações atuais
        $tipos_query = $this->wpdb->get_results("
            SELECT tipo_manifestacao, COUNT(*) as total
            FROM {$this->table_solicitacoes}
            GROUP BY tipo_manifestacao
        ", OBJECT_K);
        
        // Processar para o formato esperado
        $por_tipo = array(
            'reclamacao' => (object)array('total' => 0),
            'denuncia' => (object)array('total' => 0),
            'sugestao' => (object)array('total' => 0),
            'elogio' => (object)array('total' => 0),
            'informacao' => (object)array('total' => 0)
        );
        
        foreach ($tipos_query as $key => $value) {
            if (isset($por_tipo[$key])) {
                $por_tipo[$key]->total = (int)$value->total;
            }
        }
        
        // Dados por identificação nas solicitações atuais
        $identificacao_query = $this->wpdb->get_results("
            SELECT identificacao, COUNT(*) as total
            FROM {$this->table_solicitacoes}
            GROUP BY identificacao
        ", OBJECT_K);
        
        // Processar para o formato esperado
        $por_identificacao = array(
            'identificado' => (object)array('total' => 0),
            'anonimo' => (object)array('total' => 0)
        );
        
        foreach ($identificacao_query as $key => $value) {
            if (isset($por_identificacao[$key])) {
                $por_identificacao[$key]->total = (int)$value->total;
            }
        }
        
        // BUSCAR TODOS OS DADOS DAS ESTATÍSTICAS HISTÓRICAS
        $stats_historicas = $this->wpdb->get_results("
            SELECT * 
            FROM {$this->table_estatisticas_historicas}
            ORDER BY ano, mes
        ");
        
        // Combinar dados históricos
        foreach ($stats_historicas as $stat) {
            // Acumular totais
            $total += (int)$stat->total_manifestacoes;
            $pendentes += (int)$stat->pendentes;
            $em_analise += (int)$stat->em_analise;
            $concluidas += (int)$stat->encerradas;
            $indeferidas += (int)$stat->indeferidas;
            
            // Acumular tipo de resposta
            $sistema += (int)$stat->resposta_sistema;
            $presencial += (int)$stat->resposta_presencial;
            
            // Acumular dados por mês
            $mes_key = sprintf('%d-%02d', $stat->ano, $stat->mes);
            if (isset($por_mes[$mes_key])) {
                $por_mes[$mes_key]->total += (int)$stat->total_manifestacoes;
            } else {
                $por_mes[$mes_key] = (object)array('total' => (int)$stat->total_manifestacoes);
            }
            
            // Acumular tipos de manifestação
            $por_tipo['reclamacao']->total += (int)$stat->reclamacoes;
            $por_tipo['denuncia']->total += (int)$stat->denuncias;
            $por_tipo['sugestao']->total += (int)$stat->sugestoes;
            $por_tipo['elogio']->total += (int)$stat->elogios;
            $por_tipo['informacao']->total += (int)$stat->informacoes;
            
            // Acumular identificação
            $por_identificacao['identificado']->total += (int)$stat->identificadas;
            $por_identificacao['anonimo']->total += (int)$stat->anonimas;
        }
        
        // Ordenar o array por_mes cronologicamente
        ksort($por_mes);
        
        // Formatação para saída
        $por_tipo_resposta = array(
            'sistema' => (object)array('total' => $sistema),
            'presencial' => (object)array('total' => $presencial)
        );

        return array(
            'total' => $total,
            'pendentes' => $pendentes,
            'em_analise' => $em_analise,
            'concluidas' => $concluidas,
            'indeferidas' => $indeferidas,
            'por_tipo' => $por_tipo,
            'por_identificacao' => $por_identificacao,
            'por_tipo_resposta' => $por_tipo_resposta,
            'por_mes' => $por_mes
        );
    }
}