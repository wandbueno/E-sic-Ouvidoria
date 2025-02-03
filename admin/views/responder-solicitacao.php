<?php 
if (!defined('ABSPATH')) {
    exit;
}

// Verifica se $solicitacao existe
if (!isset($solicitacao)) {
    wp_die('Solicitação não encontrada');
}
?>

<div class="wrap">
    <h1>
        Responder Solicitação
        <span class="protocolo">Protocolo: <?php echo esc_html($solicitacao->protocolo); ?></span>
    </h1>
    
    <div class="solicitacao-detalhes card">
        <h2>Detalhes da Solicitação</h2>
        <table class="form-table">
            <tr>
                <th style="width: 150px;">Protocolo:</th>
                <td><?php echo esc_html($solicitacao->protocolo); ?></td>
            </tr>
            <tr>
                <th>Data:</th>
                <td><?php echo date('d/m/Y H:i', strtotime($solicitacao->data_criacao)); ?></td>
            </tr>
            <tr>
                <th>Status Atual:</th>
                <td><?php echo esc_html($solicitacao->status); ?></td>
            </tr>
            <tr>
                <th>Mensagem:</th>
                <td><?php echo nl2br(esc_html($solicitacao->mensagem)); ?></td>
            </tr>
        </table>
    </div>

    <div class="resposta-form card">
        <h2>Nova Resposta</h2>
        <form method="post" action="" id="form-resposta" enctype="multipart/form-data">
            <?php wp_nonce_field('ouvidoria_admin_nonce', 'resposta_nonce'); ?>
            <input type="hidden" name="solicitacao_id" value="<?php echo esc_attr($solicitacao->id); ?>">
            
            <table class="form-table">
                <tr>
                    <th><label for="resposta">Resposta *</label></th>
                    <td>
                        <textarea name="resposta" id="resposta" rows="5" class="large-text" required></textarea>
                    </td>
                </tr>
                <tr>
                    <th><label for="status">Atualizar Status</label></th>
                    <td>
                        <select name="status" id="status">
                            <option value="pendente">Pendente</option>
                            <option value="em_analise">Em Análise</option>
                            <option value="concluido">Concluído</option>
                        </select>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <button type="submit" class="button button-primary">Enviar Resposta</button>
            </p>

            <div id="mensagem-resposta" class="notice" style="display: none;"></div>
        </form>
    </div>
</div>