<?php if (!defined('ABSPATH')) exit; ?>

<form id="form-consulta-protocolo" class="form-consulta-inline">
    <?php wp_nonce_field('ouvidoria_public_nonce', 'consulta_nonce'); ?>
    
    <div class="form-row">
        <div class="input-group">
            <label for="protocolo">Número do Protocolo *</label>
            <div class="input-button-group">
                <input type="text" 
                       id="protocolo" 
                       name="protocolo" 
                       required 
                       placeholder="Digite o número do protocolo">
                <button type="submit" class="submit-button">
                    <span class="button-text">Consultar</span>
                    <div class="loading-spinner" style="display: none;">
                        <div class="spinner"></div>
                        <span>Consultando...</span>
                    </div>
                </button>
            </div>
        </div>
    </div>
</form>

<!-- Modal com Resultado -->
<div id="modal-resultado" class="modal-consulta" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Detalhes da Solicitação</h3>
            <button id="download-pdf" class="pdf-button">
                <svg viewBox="0 0 24 24" width="20" height="20">
                    <path fill="currentColor" d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/>
                </svg>
                Salvar PDF
            </button>
            <button class="fechar-modal" title="Fechar">&times;</button>
        </div>

        <div class="resultado-content">
            <!-- Preenchido via JavaScript -->
        </div>

        <div class="respostas-section" style="display: none;">
            <h4>Respostas</h4>
            <div class="respostas-list">
                <!-- Preenchido via JavaScript -->
            </div>
        </div>
    </div>
</div>
<style>
.form-consulta-inline {
    margin-bottom: 20px;
	width: 100%;
}

.input-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #333;
}

.input-button-group {
    display: flex;
    gap: 10px;
    align-items: flex-start;
	 width: 100%; 
}

.input-button-group input {
    flex: 1;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 16px;
    min-width: 300px; /* Aumenta a largura mínima do campo */
}

.input-button-group .submit-button {
    padding: 12px 20px;
    background: #0073aa;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    white-space: nowrap;

    display: flex;
    align-items: center;
}

.input-button-group .submit-button:hover {
    background: #005177;
}

/* Modal */
.modal-consulta {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 9999;
}

.modal-content {
    position: relative;
    background: white;
    width: 90%;
    max-width: 1000px;
    margin: 5% auto;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
    max-height: 90vh;
    overflow-y: auto;
}

/* Cabeçalho do Modal */
.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-bottom: 15px;
    margin-bottom: 20px;
    border-bottom: 1px solid #dee2e6;
}

.modal-header h3 {
    margin: 0;
    font-size: 1.5em;
    color: #333;
}

/* Botão PDF */
.pdf-button {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    background: #0073aa;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    transition: background-color 0.3s ease;
    margin-right: 15px;
}

.pdf-button:hover {
    background: #005177;
}

/* Botão Fechar */
.fechar-modal {
    background: none;
    border: none;
    font-size: 24px;
    color: #666;
    cursor: pointer;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.fechar-modal:hover {
    color: #333;
}

/* Conteúdo do Resultado */
.resultado-content dl {
    display: grid;
    grid-template-columns: 150px 1fr;
    gap: 10px;
    margin: 0;
}

.resultado-content dt {
    font-weight: 600;
    color: #666;
}

.resultado-content dd {
    margin: 0;
    color: #333;
}

/* Seção de Respostas */
.respostas-section {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #dee2e6;
}

.respostas-section h4 {
    margin: 0 0 15px 0;
    color: #333;
}

.resposta-item {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 6px;
    margin-bottom: 15px;
    border: 1px solid #e9ecef;
}

.resposta-meta {
    display: flex;
    justify-content: space-between;
    color: #666;
    font-size: 0.9em;
    margin-bottom: 10px;
    padding-bottom: 10px;
    border-bottom: 1px solid #dee2e6;
}

/* Links de Anexo */
.anexo-link {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 6px 12px;
    background: #f8f9fa;
    border: 1px solid #ddd;
    border-radius: 4px;
    color: #0073aa;
    text-decoration: none;
    font-size: 0.9em;
    transition: all 0.2s ease;
}

.anexo-link:hover {
    background: #e9ecef;
    border-color: #0073aa;
}

.anexo-link svg {
    width: 16px;
    height: 16px;
}

/* Loading Spinner */
.loading-spinner {
    display: none;
}

.spinner {
    width: 16px;
    height: 16px;
    border: 2px solid #ffffff;
    border-radius: 50%;
    border-top-color: transparent;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Responsividade */
@media (max-width: 768px) {
    .resultado-content dl {
        grid-template-columns: 1fr;
    }
    
    .resultado-content dt {
        padding-bottom: 5px;
    }
    
    .resultado-content dd {
        padding-bottom: 10px;
        margin-bottom: 10px;
        border-bottom: 1px solid #eee;
    }
    
    .resposta-meta {
        flex-direction: column;
        gap: 5px;
    }
}

</style>

<script>
jQuery(document).ready(function($) {
    var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
    
    // Fechar modal
    $('.fechar-modal').on('click', function() {
        $('#modal-resultado').fadeOut();
        $('body').css('overflow', 'auto');
    });

    // Fechar ao clicar fora
    $(window).on('click', function(e) {
        if ($(e.target).is('.modal-consulta')) {
            $('#modal-resultado').fadeOut();
            $('body').css('overflow', 'auto');
        }
    });

    // Consulta de protocolo
    $('#form-consulta-protocolo').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $submit = $form.find('.submit-button');
        var $buttonText = $submit.find('.button-text');
        var $loadingSpinner = $submit.find('.loading-spinner');
        var protocolo = $('#protocolo').val();
        
        if (!protocolo) {
            alert('Por favor, digite o número do protocolo');
            return;
        }
        
        // Mostrar loading
        $submit.prop('disabled', true);
        $buttonText.hide();
        $loadingSpinner.show();
        
        // Limpar conteúdo anterior
        $('.resultado-content').empty();
        $('.respostas-list').empty();
        $('.respostas-section').hide();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'consultar_protocolo',
                nonce: $('#consulta_nonce').val(),
                protocolo: protocolo
            },
            success: function(response) {
                if (response.success) {
                    var data = response.data;
                    
                    // Preencher informações básicas
                    var html = '<dl>';
                    html += '<dt>Protocolo:</dt><dd>' + (data.protocolo || 'N/A') + '</dd>';
                    html += '<dt>Status:</dt><dd>' + (data.status || 'N/A') + '</dd>';
                    html += '<dt>Data:</dt><dd>' + (data.data || 'N/A') + '</dd>';
                    html += '<dt>Tipo de Manifestação:</dt><dd>' + (data.tipo || 'N/A') + '</dd>';
                    
                    if (data.identificacao === 'identificado') {
                        html += '<dt>Identificação:</dt><dd>Identificado</dd>';
                        html += '<dt>Nome:</dt><dd>' + (data.nome || 'N/A') + '</dd>';
						html += '<dt>CPF/CNPJ:</dt><dd>' + (data.cpf_cnpj || 'N/A') + '</dd>';
                        if (data.email) html += '<dt>E-mail:</dt><dd>' + data.email + '</dd>';
                        if (data.telefone) html += '<dt>Telefone:</dt><dd>' + data.telefone + '</dd>';
						if (data.endereco) html += '<dt>Endereço:</dt><dd>' + data.endereco.replace(/\n/g, '<br>') + '</dd>';
                    } else {
                        html += '<dt>Identificação:</dt><dd>Anônimo</dd>';
                    }
                    
                    html += '<dt>Mensagem:</dt><dd>' + (data.mensagem || 'N/A') + '</dd>';
                    
                    if (data.arquivo && data.arquivo.url) {
                        html += '<dt>Anexo:</dt><dd><a href="' + data.arquivo.url + '" target="_blank" class="anexo-link">';
                        html += '<svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm4 18H6V4h7v5h5v11z"/></svg>';
                        html += data.arquivo.nome + '</a></dd>';
                    }
                    
                    html += '</dl>';
                    
                    $('.resultado-content').html(html);
                    
                    // Preencher respostas se existirem
                    if (data.respostas && data.respostas.length > 0) {
                        var respostasHtml = '';
                        data.respostas.forEach(function(resp) {
                            respostasHtml += '<div class="resposta-item">';
                            respostasHtml += '<div class="resposta-meta">';
                            respostasHtml += '<span>Respondido por: ' + (resp.respondente || 'Administrador') + '</span>';
                            respostasHtml += '<span>' + (resp.data || 'N/A') + '</span>';
                            respostasHtml += '</div>';
                            respostasHtml += '<div class="resposta-texto">' + (resp.texto || 'N/A') + '</div>';
                            
                            if (resp.arquivo && resp.arquivo.url) {
                                respostasHtml += '<a href="' + resp.arquivo.url + '" target="_blank" class="anexo-link">';
                                respostasHtml += '<svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm4 18H6V4h7v5h5v11z"/></svg>';
                                respostasHtml += resp.arquivo.nome + '</a>';
                            }
                            
                            respostasHtml += '</div>';
                        });
                        
                        $('.respostas-list').html(respostasHtml);
                        $('.respostas-section').show();
                    }
                    
                    // Mostrar modal com resultado
                    $('#modal-resultado').fadeIn();
                    $('body').css('overflow', 'hidden');
                } else {
                    alert(response.data || 'Protocolo não encontrado ou erro na consulta.');
                }
            },
            error: function() {
                alert('Erro ao processar a consulta. Por favor, tente novamente.');
            },
            complete: function() {
                $submit.prop('disabled', false);
                $buttonText.show();
                $loadingSpinner.hide();
            }
        });
    });

    // Download do PDF
    $('#download-pdf').on('click', function() {
        var protocolo = $('#protocolo').val();
        if (!protocolo) return;
        
        window.location.href = ajaxurl + '?action=gerar_pdf_protocolo&protocolo=' + protocolo + '&nonce=' + $('#consulta_nonce').val();
    });
});
</script>