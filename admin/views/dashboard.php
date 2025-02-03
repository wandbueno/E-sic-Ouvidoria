<?php
if (!defined('ABSPATH')) {
    exit;
}

// Buscar estatísticas
$stats = $this->database->get_estatisticas();

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
?>

<div class="wrap">
    <h1>Dashboard Ouvidoria</h1>

    <!-- Cards de Estatísticas -->
<div class="ouvidoria-stats-grid">
    <div class="ouvidoria-stat-card card-total">
        <div class="icon-wrapper">
            <span class="dashicons dashicons-clipboard"></span>
        </div>
        <div class="stat-content">
            <div class="stat-number"><?php echo number_format($stats['total'], 0, ',', '.'); ?></div>
            <div class="stat-label">Total de Solicitações</div>
        </div>
    </div>

    <div class="ouvidoria-stat-card card-pendente">
        <div class="icon-wrapper">
            <span class="dashicons dashicons-clock"></span>
        </div>
        <div class="stat-content">
            <div class="stat-number"><?php echo number_format($stats['pendentes'], 0, ',', '.'); ?></div>
            <div class="stat-label">Pendentes</div>
        </div>
    </div>

    <div class="ouvidoria-stat-card card-analise">
        <div class="icon-wrapper">
            <span class="dashicons dashicons-search"></span>
        </div>
        <div class="stat-content">
            <div class="stat-number"><?php echo number_format($stats['em_analise'], 0, ',', '.'); ?></div>
            <div class="stat-label">Em Análise</div>
        </div>
    </div>

    <div class="ouvidoria-stat-card card-concluida">
        <div class="icon-wrapper">
            <span class="dashicons dashicons-yes-alt"></span>
        </div>
        <div class="stat-content">
            <div class="stat-number"><?php echo number_format($stats['concluidas'], 0, ',', '.'); ?></div>
            <div class="stat-label">Concluídas</div>
        </div>
    </div>

    <div class="ouvidoria-stat-card card-indeferida">
        <div class="icon-wrapper">
            <span class="dashicons dashicons-dismiss"></span>
        </div>
        <div class="stat-content">
            <div class="stat-number"><?php echo number_format($stats['indeferidas'], 0, ',', '.'); ?></div>
            <div class="stat-label">Indeferidas</div>
        </div>
    </div>
</div>

    <!-- Lista de Solicitações -->
    <div class="ouvidoria-list-section">
        <h2>Últimas Solicitações</h2>
        
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
                        <th>Tipo Manifestação</th>
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
                                <td>
                                    <?php if ($item->identificacao === 'anonimo'): ?>
                                        <span class="badge badge-secondary">Anônimo</span>
                                    <?php else: ?>
                                        <span class="badge badge-info">Identificado</span>
                                        <br>
                                        <small><?php echo esc_html($item->nome); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                    $tipos = [
                                        'reclamacao' => 'Reclamação',
                                        'denuncia' => 'Denúncia',
                                        'sugestao' => 'Sugestão',
                                        'elogio' => 'Elogio'
                                    ];
                                    echo isset($tipos[$item->tipo_manifestacao]) ? $tipos[$item->tipo_manifestacao] : $item->tipo_manifestacao;
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                    $status_classes = [
                                        'pendente' => 'status-pendente',
                                        'em_analise' => 'status-analise',
                                        'respondida' => 'status-respondida',
                                        'encerrada' => 'status-encerrada'
                                    ];
                                    $status_labels = [
                                        'pendente' => 'Pendente',
                                        'em_analise' => 'Em Análise',
                                        'respondida' => 'Respondida',
                                        'encerrada' => 'Encerrada'
                                    ];
                                    $status_class = isset($status_classes[$item->status]) ? $status_classes[$item->status] : '';
                                    $status_label = isset($status_labels[$item->status]) ? $status_labels[$item->status] : $item->status;
                                    ?>
                                    <span class="status-badge <?php echo $status_class; ?>">
                                        <?php echo $status_label; ?>
                                    </span>
                                </td>
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
                            <td colspan="6">Nenhuma solicitação encontrada.</td>
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
</div>

<style>
/* Grid de estatísticas */
.ouvidoria-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

/* Cards de estatísticas */
.ouvidoria-stat-card {
    display: flex;
    align-items: center;
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: transform 0.2s ease;
}

.ouvidoria-stat-card:hover {
    transform: translateY(-2px);
}

/* Ícones */
.icon-wrapper {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 15px;
}

.icon-wrapper .dashicons {
    font-size: 24px;
    width: 24px;
    height: 24px;
    color: white;
}

/* Conteúdo dos cards */
.stat-content {
    flex-grow: 1;
}

.stat-number {
    font-size: 28px;
    font-weight: bold;
    line-height: 1.2;
    margin-bottom: 4px;
}

.stat-label {
    font-size: 14px;
    color: #666;
}

/* Cores específicas para cada card */
.card-total {
    border-left: 4px solid #2271b1;
}
.card-total .icon-wrapper {
    background-color: #2271b1;
}
.card-total .stat-number {
    color: #2271b1;
}

.card-pendente {
    border-left: 4px solid #856404;
}
.card-pendente .icon-wrapper {
    background-color: #856404;
}
.card-pendente .stat-number {
    color: #856404;
}

.card-analise {
    border-left: 4px solid #004085;
}
.card-analise .icon-wrapper {
    background-color: #004085;
}
.card-analise .stat-number {
    color: #004085;
}

.card-concluida {
    border-left: 4px solid #155724;
}
.card-concluida .icon-wrapper {
    background-color: #155724;
}
.card-concluida .stat-number {
    color: #155724;
}

/* Add style for indeferida card */
.card-indeferida {
    border-left: 4px solid #dc3545;
}
.card-indeferida .icon-wrapper {
    background-color: #dc3545;
}
.card-indeferida .stat-number {
    color: #dc3545;
}

/* Status badges na tabela */
.status-badge {
    padding: 4px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: 500;
    text-transform: uppercase;
}

.status-pendente {
    background-color: #ffeeba;
    color: #856404;
}

.status-analise {
    background-color: #b8daff;
    color: #004085;
}

.status-respondida {
    background-color: #c3e6cb;
    color: #155724;
}

.status-encerrada {
    background-color: #d6d8db;
    color: #383d41;
}

/* Demais estilos mantidos... */
</style>