<?php
function ouvidoria_consulta_shortcode($atts) {
    // Define os atributos padrão e mescla com os fornecidos
    $atts = shortcode_atts(array(
        'largura_total' => '600px',
        'cor_botao' => '#0073aa',
        'cor_hover' => '#005177',
        'cor_texto' => '#ffffff'
    ), $atts);

    ob_start();
    ?>
    <div class="ouvidoria-consulta-wrapper" style="max-width: <?php echo esc_attr($atts['largura_total']); ?>;">
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
                        <button type="submit" class="submit-button" 
                                style="background-color: <?php echo esc_attr($atts['cor_botao']); ?>; 
                                       color: <?php echo esc_attr($atts['cor_texto']); ?>;">
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
    </div>

    <!-- Modal com Resultado -->
    <div id="modal-resultado" class="modal-consulta" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Detalhes da Solicitação</h3>
                <button class="fechar-modal" title="Fechar">&times;</button>
            </div>
            <div class="resultado-content">
                <!-- Preenchido via JavaScript -->
            </div>
        </div>
    </div>

    <style>
    /* Container principal com largura personalizada */
<style>
    .ouvidoria-consulta-wrapper {
        margin: 0 auto;
        width: 100%;
    }

    .form-consulta-inline {
        width: 100%;
    }

    .input-button-group {
        display: flex;
        gap: 10px;
        width: 100%;
    }

    .input-button-group input {
        flex: 1;
        min-width: 0;
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 14px;
    }

    .input-button-group .submit-button {
        padding: 8px 20px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 14px;
        white-space: nowrap;
        height: 35px;
        display: flex;
        align-items: center;
        transition: background-color 0.3s ease;
    }

    .input-button-group .submit-button:hover {
        background-color: <?php echo esc_attr($atts['cor_hover']); ?> !important;
    }

    /* Label */
    .input-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 500;
        color: #333;
    }

    /* Loading spinner */
    .loading-spinner {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }

    .spinner {
        width: 20px;
        height: 20px;
        border: 3px solid <?php echo esc_attr($atts['cor_texto']); ?>;
        border-radius: 50%;
        border-top-color: transparent;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        to { transform: rotate(360deg); }
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
        background: white;
        width: 90%;
        max-width: 600px;
        margin: 5% auto;
        padding: 20px;
        border-radius: 8px;
        position: relative;
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 1px solid #ddd;
    }

    .fechar-modal {
        background: none;
        border: none;
        font-size: 24px;
        cursor: pointer;
        color: #666;
    }

    /* Responsividade */
    @media (max-width: 768px) {
        .input-button-group {
            flex-direction: column;
        }

        .input-button-group .submit-button {
            width: 100%;
            justify-content: center;
        }
    }
    </style>

    <script>
    jQuery(document).ready(function($) {
        $('#ouvidoria-consulta-form').on('submit', function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $submit = $form.find('button[type="submit"]');
            var $mensagem = $('#ouvidoria-mensagem');
            var $resultado = $('#ouvidoria-resultado');
            var $detalhes = $('.ouvidoria-detalhes');
            
            var protocolo = $('#protocolo').val();
            
            if (!protocolo) {
                $mensagem.addClass('erro').html('Por favor, informe o número do protocolo').show();
                return;
            }

            $submit.prop('disabled', true).text('Consultando...');
            $mensagem.hide();
            
            $.ajax({
                url: '<?php echo admin_url("admin-ajax.php"); ?>',
                type: 'POST',
                data: {
                    action: 'consultar_protocolo',
                    nonce: '<?php echo wp_create_nonce("ouvidoria_public_nonce"); ?>',
                    protocolo: protocolo
                },
                success: function(response) {
                    console.log('Resposta:', response);
                    
                    if (response.success) {
                        var html = '<p><strong>Protocolo:</strong> ' + response.data.protocolo + '</p>';
                        html += '<p><strong>Status:</strong> ' + response.data.status + '</p>';
                        html += '<p><strong>Data:</strong> ' + response.data.data + '</p>';
                        html += '<p><strong>Tipo:</strong> ' + response.data.tipo + '</p>';

                        if (response.data.respostas && response.data.respostas.length > 0) {
                            html += '<div class="ouvidoria-respostas">';
                            html += '<h4>Respostas:</h4>';
                            response.data.respostas.forEach(function(resp) {
                                html += '<div class="ouvidoria-resposta">';
                                html += '<p><strong>Data:</strong> ' + resp.data + '</p>';
                                html += '<p>' + resp.texto + '</p>';
                                html += '</div>';
                            });
                            html += '</div>';
                        }

                        $detalhes.html(html);
                        $resultado.slideDown();
                    } else {
                        $mensagem.addClass('erro').html(response.data).show();
                        $resultado.hide();
                    }
                },
                error: function() {
                    $mensagem.addClass('erro').html('Erro ao processar a consulta. Tente novamente.').show();
                    $resultado.hide();
                },
                complete: function() {
                    $submit.prop('disabled', false).text('Consultar');
                }
            });
        });
    });
    </script>
    <?php
    return ob_get_clean();
}

add_shortcode('ouvidoria_consulta', 'ouvidoria_consulta_shortcode');