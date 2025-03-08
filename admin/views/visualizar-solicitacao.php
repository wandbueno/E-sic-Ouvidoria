<?php
if (!defined('ABSPATH')) {
    exit;
}

// Configurar timezone para Brasília
date_default_timezone_set('America/Sao_Paulo');

// Pegar ID da solicitação
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Buscar dados da solicitação
$solicitacao = $this->database->get_solicitacao($id);

// Buscar respostas da solicitação
$respostas = $this->database->get_respostas($id);

// Se não encontrou a solicitação
if (!$solicitacao) {
    wp_die('Solicitação não encontrada.');
}

$status_encerrado = $solicitacao->status === 'encerrado';
?>

<div class="wrap">
    <h1>Visualizar Solicitação</h1>
    
    <div class="ouvidoria-visualizar">
        <!-- Cabeçalho com informações principais -->
        <div class="ouvidoria-header">
            <div class="header-info">
                <h2>Protocolo: <?php echo esc_html($solicitacao->protocolo); ?></h2>
                <p class="status-badge status-<?php echo esc_attr($solicitacao->status); ?>">
                    <?php echo esc_html(ucfirst($solicitacao->status)); ?>
                </p>
            </div>
            
            <div class="header-actions">
                <a href="<?php echo admin_url('admin-ajax.php?action=gerar_pdf_solicitacao&id=' . $id . '&nonce=' . wp_create_nonce('gerar_pdf_solicitacao')); ?>" 
                   class="button" target="_blank">
                    <span class="dashicons dashicons-pdf"></span> Gerar PDF
                </a>
                <div class="header-date">
                    Data: <?php echo wp_date('d/m/Y H:i', strtotime($solicitacao->data_criacao)); ?>
				</div>
            </div>
        </div>

        <!-- Detalhes da solicitação -->
        <div class="ouvidoria-details">
            <div class="detail-section">
                <h3>Informações da Solicitação</h3>
                <table class="form-table">
                    <tr>
                        <th>Tipo de Manifestação:</th>
                        <td><?php 
                            $tipos = array(
                                'reclamacao' => 'Reclamação',
                                'denuncia' => 'Denúncia',
                                'sugestao' => 'Sugestão',
                                'elogio' => 'Elogio',
                                'informacao' => 'Acesso à Informação'
                            );
                            echo isset($tipos[$solicitacao->tipo_manifestacao]) ? 
                                esc_html($tipos[$solicitacao->tipo_manifestacao]) : 
                                esc_html(ucfirst($solicitacao->tipo_manifestacao));
                        ?></td>
                    </tr>
                    <tr>
                        <th>Forma de Recebimento:</th>
                        <td><?php 
                            $tipos_resposta = array(
                                'sistema' => 'Pelo Sistema (Protocolo)',
                                'presencial' => 'Buscar/Consultar Pessoalmente'
                            );
                            echo isset($tipos_resposta[$solicitacao->tipo_resposta]) ? 
                                esc_html($tipos_resposta[$solicitacao->tipo_resposta]) : 
                                esc_html($solicitacao->tipo_resposta);
                        ?></td>
                    </tr>
                    <tr>
                        <th>Identificação:</th>
                        <td><?php echo esc_html(ucfirst($solicitacao->identificacao)); ?></td>
                    </tr>
                    <?php if ($solicitacao->identificacao === 'identificado'): ?>
                        <tr>
                            <th>Nome:</th>
                            <td><?php echo esc_html($solicitacao->nome); ?></td>
                        </tr>
						 <tr>
							<th>CPF/CNPJ:</th>
							<td><?php echo esc_html($solicitacao->cpf_cnpj); ?></td>
						</tr>
                        <?php if (!empty($solicitacao->email)): ?>
                            <tr>
                                <th>E-mail:</th>
                                <td><?php echo esc_html($solicitacao->email); ?></td>
                            </tr>
                        <?php endif; ?>
                        <?php if (!empty($solicitacao->telefone)): ?>
                            <tr>
                                <th>Telefone:</th>
                                <td><?php echo esc_html($solicitacao->telefone); ?></td>
                            </tr>
							 <tr>
								<th>Endereço:</th>
								<td><?php echo nl2br(esc_html($solicitacao->endereco)); ?></td>
							</tr>
                        <?php endif; ?>
                    <?php endif; ?>
                </table>
            </div>

            <div class="detail-section">
                <h3>Mensagem</h3>
                <div class="mensagem-content">
                    <?php echo nl2br(esc_html($solicitacao->mensagem)); ?>
                </div>
                <?php if (!empty($solicitacao->arquivo)): ?>
                    <div class="arquivo-anexo">
                        <strong>Arquivo Anexo:</strong>
                        <a href="<?php echo esc_url($solicitacao->arquivo); ?>" target="_blank">
                            <span class="dashicons dashicons-paperclip"></span>
                            Visualizar Anexo
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Seção de Respostas -->
            <div class="detail-section respostas-section">
                <h3>Respostas</h3>
                <?php if (!empty($respostas)): ?>
                    <div class="respostas-lista">
                        <?php foreach ($respostas as $resposta): 
                            // Converter a data da resposta para o timezone de Brasília
                            $data_resposta = new DateTime($resposta->data_resposta, new DateTimeZone('UTC'));
                            $data_resposta->setTimezone(new DateTimeZone('America/Sao_Paulo'));
                        ?>
                            <div class="resposta-item">
                                <div class="resposta-header">
                                    <div class="resposta-meta">
                                        <div class="respondente">
                                            <strong>Respondido por:</strong> <?php 
                                            $user_info = get_userdata($resposta->respondido_por);
                                            echo esc_html($user_info ? $user_info->display_name : 'Usuário');
                                            ?>
                                            <?php if (!empty($resposta->status)): ?>
                                                <span class="resposta-status">(<?php echo esc_html(ucfirst($resposta->status)); ?>)</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="resposta-data">
											<?php echo wp_date('d/m/Y H:i', strtotime($resposta->data_resposta)); ?>
  										</div>
                                    </div>
                                </div>
                                <div class="resposta-content">
                                    <?php echo wpautop(esc_html($resposta->resposta)); ?>
                                    <?php if (!empty($resposta->arquivo)): ?>
                                        <div class="arquivo-anexo">
                                            <a href="<?php echo wp_get_attachment_url($resposta->arquivo); ?>" target="_blank">
                                                <span class="dashicons dashicons-paperclip"></span>
                                                Baixar Anexo
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p>Nenhuma resposta registrada.</p>
                <?php endif; ?>
            </div>

            <?php if (!$status_encerrado): ?>
            <!-- Formulário de Resposta -->
            <div class="detail-section">
                <h3>Adicionar Resposta</h3>
                <form id="form-resposta" method="post" enctype="multipart/form-data">
                    <?php wp_nonce_field('ouvidoria_admin_nonce', 'resposta_nonce'); ?>
                    <input type="hidden" name="solicitacao_id" value="<?php echo esc_attr($solicitacao->id); ?>">
                    
                    <table class="form-table">
                        <tr>
                            <th><label for="resposta">Resposta:</label></th>
                            <td>
                                <textarea name="resposta" id="resposta" rows="5" class="large-text" required></textarea>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="novo_status">Novo Status:</label></th>
                            <td>
                                <select name="novo_status" id="novo_status">
                                    <option value="pendente" <?php selected($solicitacao->status, 'pendente'); ?>>Pendente</option>
                                    <option value="em_analise" <?php selected($solicitacao->status, 'em_analise'); ?>>Em Análise</option>
                                    <option value="encerrado" <?php selected($solicitacao->status, 'encerrado'); ?>>Encerrado</option>
                                    <option value="indeferida" <?php selected($solicitacao->status, 'indeferida'); ?>>Indeferida</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="arquivo">Anexo (opcional):</label></th>
                            <td>
                                <input type="file" name="arquivo" id="arquivo">
                                <p class="description">Arquivos permitidos: PDF, DOC, DOCX, JPG, PNG (máx. 5MB)</p>
                            </td>
                        </tr>
                    </table>

                    <p class="submit">
                        <button type="submit" class="button button-primary">Enviar Resposta</button>
                    </p>
                </form>
            </div>
            <?php else: ?>
            <div class="detail-section notice-info">
                <p><strong>Esta solicitação está encerrada.</strong> Não é possível adicionar novas respostas.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
/* Estilos gerais */
.ouvidoria-visualizar {
    background: #fff;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-top: 20px;
}

/* Cabeçalho */
.ouvidoria-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 2px solid #eee;
    padding-bottom: 20px;
    margin-bottom: 30px;
}

.header-info {
    display: flex;
    align-items: center;
    gap: 20px;
}

.header-info h2 {
    margin: 0;
    font-size: 24px;
    color: #23282d;
}

/* Seções */
.detail-section {
    background: #f9f9f9;
    border-radius: 8px;
    padding: 25px;
    margin-bottom: 30px;
    border: 1px solid #e5e5e5;
}

.detail-section h3 {
    margin: 0 0 20px 0;
    padding-bottom: 15px;
    border-bottom: 2px solid #e5e5e5;
    color: #23282d;
    font-size: 18px;
}

/* Tabela de informações */
.form-table {
    margin: 0;
}

.form-table th {
    width: 200px;
    padding: 15px;
    font-weight: 600;
    color: #23282d;
}

.form-table td {
    padding: 15px 10px;
}

/* Mensagem e anexos */
.mensagem-content {
    background: #fff;
    padding: 20px;
    border-radius: 6px;
    border: 1px solid #e5e5e5;
    margin-bottom: 15px;
}

.arquivo-anexo {
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #e5e5e5;
}

.arquivo-anexo a {
    display: inline-flex;
    align-items: center;
    text-decoration: none;
    color: #0073aa;
    padding: 8px 12px;
    background: #f0f0f1;
    border-radius: 4px;
    transition: all 0.2s ease;
}

.arquivo-anexo a:hover {
    background: #e5e5e5;
    color: #006799;
}

.arquivo-anexo .dashicons {
    margin-right: 8px;
}

/* Respostas */
.respostas-lista {
    margin-top: 20px;
}

.resposta-item {
    background: #fff;
    border: 1px solid #e5e5e5;
    border-radius: 6px;
    margin-bottom: 20px;
    overflow: hidden;
}

.resposta-header {
    background: #f8f9fa;
    padding: 15px 20px;
    border-bottom: 1px solid #e5e5e5;
}

.resposta-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    color: #666;
    font-size: 0.9em;
}

.respondente {
    display: flex;
    align-items: center;
    gap: 10px;
}

.resposta-status {
    font-size: 0.9em;
    color: #666;
    margin-left: 10px;
}

.resposta-content {
    padding: 20px;
}

/* Status badges */
.status-badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 500;
    text-transform: uppercase;
}

.status-pendente {
    background-color: #fff3cd;
    color: #856404;
}

.status-em_analise {
    background-color: #cce5ff;
    color: #004085;
}

.status-respondida {
    background-color: #d4edda;
    color: #155724;
}

.status-encerrado {
    background-color: #e2e3e5;
    color: #383d41;
}

.status-indeferida {
    background-color: #f8d7da;
    color: #721c24;
}

/* Formulário de resposta */
#form-resposta {
    margin-top: 20px;
}

#form-resposta .form-table {
    background: #fff;
    padding: 20px;
    border-radius: 6px;
    border: 1px solid #e5e5e5;
}

#form-resposta textarea {
    width: 100%;
    min-height: 150px;
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

#form-resposta select {
    min-width: 200px;
    padding: 8px;
}

#form-resposta .submit {
    margin-top: 20px;
    padding: 20px 0 0;
    border-top: 1px solid #e5e5e5;
}

/* Mensagem de encerrado */
.notice-info {
    background-color: #f0f6fc;
    border-left: 4px solid #72aee6;
}

.notice-info p {
    margin: 0;
    padding: 12px;
    color: #23282d;
}

/* Responsividade */
@media screen and (max-width: 782px) {
    .ouvidoria-header {
        flex-direction: column;
        gap: 15px;
        text-align: center;
    }
    
    .header-info {
        flex-direction: column;
        gap: 10px;
    }
    
    .form-table th {
        width: 100%;
        display: block;
        padding-bottom: 0;
    }
    
    .form-table td {
        width: 100%;
        display: block;
    }
    
    .resposta-meta {
        flex-direction: column;
        gap: 10px;
        text-align: center;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    $('#form-resposta').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $submitButton = $form.find('button[type="submit"]');
        
        $submitButton.prop('disabled', true).html('<span class="dashicons dashicons-update-alt spin"></span> Enviando...');
        
        var formData = new FormData(this);
        formData.append('action', 'adicionar_resposta');
        formData.append('nonce', $('#resposta_nonce').val());
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data || 'Erro ao adicionar resposta');
                    $submitButton.prop('disabled', false).html('Enviar Resposta');
                }
            },
            error: function(xhr, status, error) {
                console.error('Erro:', error);
                alert('Erro ao processar a resposta. Por favor, tente novamente.');
                $submitButton.prop('disabled', false).html('Enviar Resposta');
            }
        });
    });
});
</script>