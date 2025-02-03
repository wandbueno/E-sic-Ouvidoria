<?php
if (!defined('ABSPATH')) {
    exit;
}

// Parâmetros da listagem
$page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';

// Buscar solicitações
$resultado = $this->database->get_solicitacoes(array(
    'page' => $page,
    'search' => $search,
    'status' => $status
));

// Debug
error_log('Resultado da busca: ' . print_r($resultado, true));
?>

<div class="wrap">
    <h1 class="wp-heading-inline">Solicitações</h1>
    <a href="<?php echo admin_url('admin.php?page=ouvidoria-nova'); ?>" class="page-title-action">Adicionar Nova</a>
    
    <form method="get">
        <input type="hidden" name="page" value="ouvidoria">
        
        <div class="tablenav top">
            <div class="alignleft actions">
                <select name="status">
                    <option value="">Todos os status</option>
                    <option value="pendente" <?php selected($status, 'pendente'); ?>>Pendente</option>
                    <option value="em_analise" <?php selected($status, 'em_analise'); ?>>Em Análise</option>
                    <option value="respondida" <?php selected($status, 'respondida'); ?>>Respondida</option>
                    <option value="encerrada" <?php selected($status, 'encerrada'); ?>>Encerrada</option>
                </select>
                <input type="submit" class="button" value="Filtrar">
            </div>
            
            <div class="alignright">
                <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Buscar solicitações...">
                <input type="submit" class="button" value="Buscar">
            </div>
        </div>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Protocolo</th>
                    <th>Data</th>
                    <th>Identificação</th>
                    <th>Nome</th>
                    <th>Tipo</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($resultado['items'])): ?>
                    <?php foreach ($resultado['items'] as $item): ?>
                        <tr>
                            <td><?php echo esc_html($item->protocolo); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($item->data_criacao)); ?></td>
                            <td><?php echo esc_html($item->identificacao); ?></td>
                            <td><?php echo $item->identificacao === 'anonimo' ? '-' : esc_html($item->nome); ?></td>
                            <td><?php echo esc_html($item->tipo_manifestacao); ?></td>
                            <td><?php echo esc_html($item->status); ?></td>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=ouvidoria-visualizar&id=' . $item->id); ?>" 
                                   class="button button-small">
                                    Visualizar
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7">Nenhuma solicitação encontrada.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <?php if ($resultado['pages'] > 1): ?>
            <div class="tablenav bottom">
                <div class="tablenav-pages">
                    <?php
                    echo paginate_links(array(
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => '&laquo;',
                        'next_text' => '&raquo;',
                        'total' => $resultado['pages'],
                        'current' => $page
                    ));
                    ?>
                </div>
            </div>
        <?php endif; ?>
    </form>
</div>