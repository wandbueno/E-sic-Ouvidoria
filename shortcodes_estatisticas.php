<?php
function ouvidoria_estatisticas_shortcode() {
    wp_enqueue_script('chartjs', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '3.7.0', true);
    wp_enqueue_style('dashicons');

    // Obter o plugin
    $plugin = sistema_ouvidoria();
    
    // Obter o ano selecionado ou usar 'todos' como padrão
    $ano_atual = isset($_GET['ano']) ? $_GET['ano'] : 'todos';
    
    // Verificar se é um valor numérico (ano específico)
    if ($ano_atual !== 'todos') {
        $ano_atual = intval($ano_atual);
    }
    
    // Buscar anos das estatísticas atuais e históricas
    $anos_atuais = $plugin->database->get_anos_disponiveis();
    $anos_historicos = $plugin->database->get_anos_historicos();
    
    // Combinar e ordenar anos únicos
    $todos_anos = array_unique(array_merge($anos_atuais, $anos_historicos));
    rsort($todos_anos); // Ordenar em ordem decrescente
    
    // Se não houver anos disponíveis, incluir o ano atual
    if (empty($todos_anos)) {
        $todos_anos = array(date('Y'));
    }
    
    // Buscar estatísticas baseadas no ano selecionado
    $stats = $plugin->database->get_estatisticas($ano_atual);

    ob_start();
    ?>
    <div class="ouvidoria-estatisticas">
        <div class="estatisticas-header">
            <div class="header-title-export">
                <h2>Estatísticas da Ouvidoria / E-Sic</h2>
                <div class="export-buttons">
                    <?php $nonce = wp_create_nonce('exportar_estatisticas'); ?>
                    <a href="<?php echo esc_url(admin_url('admin-ajax.php?action=exportar_estatisticas&tipo=pdf&ano=' . $ano_atual . '&nonce=' . $nonce)); ?>" class="export-button pdf" title="Exportar para PDF">
                        <span class="dashicons dashicons-pdf"></span>
                    </a>
                    <a href="<?php echo esc_url(admin_url('admin-ajax.php?action=exportar_estatisticas&tipo=csv&ano=' . $ano_atual . '&nonce=' . $nonce)); ?>" class="export-button csv" title="Exportar para CSV">
                        <span class="dashicons dashicons-media-spreadsheet"></span>
                    </a>
                    <a href="<?php echo esc_url(admin_url('admin-ajax.php?action=exportar_estatisticas&tipo=excel&ano=' . $ano_atual . '&nonce=' . $nonce)); ?>" class="export-button excel" title="Exportar para Excel">
                        <span class="dashicons dashicons-media-interactive"></span>
                    </a>
                </div>
            </div>
            <p>Dados atualizados em: <?php echo wp_date('d/m/Y H:i'); ?></p>

            <!-- Filtro de Ano -->
            <div class="filtro-ano">
                <label for="select-ano">Filtrar por ano:</label>
                <select id="select-ano" class="select-ano">
                    <option value="todos" <?php selected($ano_atual, 'todos'); ?>>Todas...</option>
                    <?php foreach ($todos_anos as $ano): ?>
                        <option value="<?php echo $ano; ?>" <?php selected($ano, $ano_atual); ?>>
                            <?php echo $ano; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- Cards de Estatísticas -->
        <div class="estatisticas-cards">
            <div class="card-estatistica total">
                <div class="card-icon">
                    <span class="dashicons dashicons-clipboard"></span>
                </div>
                <div class="card-content">
                    <h3>Total de Solicitações</h3>
                    <div class="numero-grande"><?php echo $stats['total']; ?></div>
                </div>
            </div>

            <div class="card-estatistica pendentes">
                <div class="card-icon">
                    <span class="dashicons dashicons-clock"></span>
                </div>
                <div class="card-content">
                    <h3>Pendentes</h3>
                    <div class="numero-grande"><?php echo $stats['pendentes']; ?></div>
                </div>
            </div>

            <div class="card-estatistica analise">
                <div class="card-icon">
                    <span class="dashicons dashicons-search"></span>
                </div>
                <div class="card-content">
                    <h3>Em Análise</h3>
                    <div class="numero-grande"><?php echo $stats['em_analise']; ?></div>
                </div>
            </div>

            <div class="card-estatistica concluidas">
                <div class="card-icon">
                    <span class="dashicons dashicons-yes-alt"></span>
                </div>
                <div class="card-content">
                    <h3>Concluídas</h3>
                    <div class="numero-grande"><?php echo $stats['concluidas']; ?></div>
                </div>
            </div>

            <div class="card-estatistica indeferidas">
                <div class="card-icon">
                    <span class="dashicons dashicons-dismiss"></span>
                </div>
                <div class="card-content">
                    <h3>Indeferidas</h3>
                    <div class="numero-grande"><?php echo $stats['indeferidas']; ?></div>
                </div>
            </div>
        </div>

        <!-- Gráficos em Grid 2x2 -->
        <div class="graficos-grid">
            <!-- Primeira linha: Gráficos de Barra -->
            <div class="graficos-row">
                <!-- Gráfico de Tipos -->
                <div class="grafico-container">
                    <h3>Tipo de Manifestação / Solicitação</h3>
                    <canvas id="graficoTipos"></canvas>
                </div>

                <!-- Gráfico por Mês -->
                <div class="grafico-container">
                    <h3>Manifestações / Solicitações por Mês</h3>
                    <canvas id="graficoPorMes"></canvas>
                </div>
            </div>

            <!-- Segunda linha: Gráficos de Pizza -->
            <div class="graficos-row tres-colunas">
                <!-- Gráfico de Status -->
                <div class="grafico-container">
                    <h3>Status das Manifestações / Solicitações</h3>
                    <canvas id="graficoStatus"></canvas>
                </div>

                <!-- Gráfico de Forma de Recebimento -->
                <div class="grafico-container">
                    <h3>Forma de Recebimento</h3>
                    <canvas id="graficoRecebimento"></canvas>
                </div>

                <div class="grafico-container">
                    <h3>Tipo de Identificação</h3>
                    <canvas id="graficoIdentificacao"></canvas>
                </div>
            </div>
        </div>
    </div>

    <style>
    .graficos-row.tres-colunas {
        grid-template-columns: repeat(3, 1fr);
    }
    
    .filtro-ano {
        margin-top: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }

    .filtro-ano label {
        font-weight: 500;
        color: #666;
    }

    .select-ano {
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 4px;
        background: white;
        font-size: 14px;
        color: #333;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .select-ano:hover {
        border-color: #0073aa;
    }

    .select-ano:focus {
        outline: none;
        border-color: #0073aa;
        box-shadow: 0 0 0 2px rgba(0,115,170,0.2);
    }

    .ouvidoria-estatisticas {
        padding: 20px;
        background: #f5f5f5;
        border-radius: 12px;
    }

    .estatisticas-header {
        text-align: center;
        margin-bottom: 30px;
    }

    /* Cards de Estatísticas */
    .estatisticas-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .card-estatistica {
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        display: flex;
        align-items: center;
        gap: 15px;
        transition: transform 0.2s ease;
    }

    .card-estatistica:hover {
        transform: translateY(-2px);
    }

    .card-icon {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f8f9fa;
    }

    .card-icon .dashicons {
        font-size: 24px;
        width: 24px;
        height: 24px;
    }

    .card-content {
        flex: 1;
    }

    .card-content h3 {
        margin: 0 0 5px 0;
        font-size: 14px;
        color: #666;
    }

    .numero-grande {
        font-size: 24px;
        font-weight: bold;
        color: #0073aa;
        line-height: 1;
    }

    .unidade {
        font-size: 14px;
        color: #666;
    }

    /* Grid de Gráficos */
    .graficos-grid {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .graficos-row {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
    }

    .grafico-container {
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .grafico-container h3 {
        margin: 0 0 20px 0;
        font-size: 16px;
        color: #333;
        text-align: center;
    }

    /* Cores específicas para os cards */
    .card-estatistica.total { border-left: 4px solid #0073aa; }
    .card-estatistica.pendentes { border-left: 4px solid #FFA500; }
    .card-estatistica.analise { border-left: 4px solid #3498db; }
    .card-estatistica.concluidas { border-left: 4px solid #2ecc71; }
    .card-estatistica.indeferidas { border-left: 4px solid #e74c3c; }
    .card-estatistica.tempo-medio { border-left: 4px solid #9b59b6; }

    /* Responsividade */
    @media (max-width: 1200px) {
        .graficos-row {
            grid-template-columns: 1fr;
        }
        .graficos-row.tres-colunas {
            grid-template-columns: 1fr 1fr;
        }
    }

    @media (max-width: 768px) {
        .estatisticas-cards {
            grid-template-columns: repeat(2, 1fr);
        }
        .graficos-row.tres-colunas {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 480px) {
        .estatisticas-cards {
            grid-template-columns: 1fr;
        }
    }

    .header-title-export {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 15px;
    }

    .header-title-export h2 {
        margin: 0;
    }

    .export-buttons {
        display: flex;
        gap: 8px;
    }

    .export-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border-radius: 4px;
        transition: all 0.2s ease;
        text-decoration: none;
    }

    .export-button:hover {
        transform: translateY(-2px);
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }

    .export-button .dashicons {
        color: white;
        font-size: 16px;
        width: 16px;
        height: 16px;
    }

    .export-button.pdf {
        background: #e74c3c;
    }

    .export-button.csv {
        background: #3498db;
    }

    .export-button.excel {
        background: #27ae60;
    }

    /* Estilo responsivo para o cabeçalho */
    @media (max-width: 767px) {
        .header-title-export {
            flex-direction: column;
            align-items: flex-start;
            gap: 10px;
        }
    }
    </style>

    <script>
    jQuery(document).ready(function($) {
        // Manipular mudança no select de ano
        $('#select-ano').on('change', function() {
            var ano = $(this).val();
            var url = new URL(window.location.href);
            url.searchParams.set('ano', ano);
            window.location.href = url.toString();
        });

        // Configurações comuns para gráficos
        Chart.defaults.font.size = 12;
        Chart.defaults.font.family = "'Helvetica Neue', 'Helvetica', 'Arial', sans-serif";

        // Gráfico de Status (Pizza)
        new Chart(document.getElementById('graficoStatus'), {
            type: 'doughnut',
            data: {
                labels: ['Pendentes', 'Em Análise', 'Concluídas', 'Indeferidas'],
                datasets: [{
                    data: [
                        <?php echo $stats['pendentes']; ?>,
                        <?php echo $stats['em_analise']; ?>,
                        <?php echo $stats['concluidas']; ?>,
                        <?php echo $stats['indeferidas']; ?>
                    ],
                    backgroundColor: ['#FFA500', '#3498db', '#2ecc71', '#e74c3c']
                }]
            },
            options: {
                responsive: true,
                plugins: { 
                    legend: { 
                        position: 'bottom',
                        labels: { padding: 20 }
                    }
                }
            }
        });

        // Gráfico de Identificação (Pizza)
        new Chart(document.getElementById('graficoIdentificacao'), {
            type: 'pie',
            data: {
                labels: ['Identificado', 'Anônimo'],
                datasets: [{
                    data: [
                        <?php echo isset($stats['por_identificacao']['identificado']) ? $stats['por_identificacao']['identificado']->total : 0; ?>,
                        <?php echo isset($stats['por_identificacao']['anonimo']) ? $stats['por_identificacao']['anonimo']->total : 0; ?>
                    ],
                    backgroundColor: ['#0073aa', '#6c757d']
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'bottom' } }
            }
        });

        // Gráfico de Tipos (Barras)
        const tiposLabels = {
            'reclamacao': 'Reclamação',
            'denuncia': 'Denúncia',
            'sugestao': 'Sugestão',
            'elogio': 'Elogio',
            'informacao': 'Informação'
        };

        const tiposData = {
            labels: [],
            datasets: [{
                label: 'Quantidade',
                data: [],
                backgroundColor: '#3498db',
                borderRadius: 6
            }]
        };

        <?php if (!empty($stats['por_tipo'])): ?>
            tiposData.labels = Object.keys(<?php echo json_encode($stats['por_tipo']); ?>).map(key => tiposLabels[key] || key);
            tiposData.datasets[0].data = Object.values(<?php echo json_encode($stats['por_tipo']); ?>).map(item => item.total);
        <?php endif; ?>

        new Chart(document.getElementById('graficoTipos'), {
            type: 'bar',
            data: tiposData,
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    }
                }
            }
        });

        // Gráfico de Forma de Recebimento (Pizza)
        new Chart(document.getElementById('graficoRecebimento'), {
            type: 'pie',
            data: {
                labels: ['Sistema', 'Presencial'],
                datasets: [{
                    data: [
                        <?php 
                        echo isset($stats['por_tipo_resposta']['sistema']) ? $stats['por_tipo_resposta']['sistema']->total : 0;
                        ?>,
                        <?php 
                        echo isset($stats['por_tipo_resposta']['presencial']) ? $stats['por_tipo_resposta']['presencial']->total : 0;
                        ?>
                    ],
                    backgroundColor: ['#9b59b6', '#f1c40f']
                }]
            },
            options: {
                responsive: true,
                plugins: { 
                    legend: { 
                        position: 'bottom',
                        labels: { padding: 20 }
                    }
                }
            }
        });

        // Gráfico por Mês (Barras)
        const mesesData = {
            labels: [],
            datasets: [{
                label: 'Manifestações',
                data: [],
                backgroundColor: '#3498db',
                borderRadius: 6
            }]
        };

        <?php if (!empty($stats['por_mes'])): ?>
		mesesData.labels = Object.keys(<?php echo json_encode($stats['por_mes']); ?>).map(mes => {
			const [ano, mesNum] = mes.split('-');
			const data = new Date(ano, parseInt(mesNum) - 1); // Subtrair 1 pois os meses em JS são 0-based
			return data.toLocaleDateString('pt-BR', {month: 'short', year: 'numeric'});
		});
		mesesData.datasets[0].data = Object.values(<?php echo json_encode($stats['por_mes']); ?>).map(item => item.total);

        <?php endif; ?>

        new Chart(document.getElementById('graficoPorMes'), {
            type: 'bar',
            data: mesesData,
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    }
                }
            }
        });
    });
    </script>
    <?php
    return ob_get_clean();
}

add_action('wp_ajax_exportar_estatisticas', 'ouvidoria_ajax_exportar_estatisticas');
add_action('wp_ajax_nopriv_exportar_estatisticas', 'ouvidoria_ajax_exportar_estatisticas');

function ouvidoria_ajax_exportar_estatisticas() {
    // Verificar o nonce para segurança
    if (!isset($_GET['nonce']) || !wp_verify_nonce($_GET['nonce'], 'exportar_estatisticas')) {
        wp_die('Link de exportação inválido ou expirado.');
    }
    
    // Obter parâmetros
    $export_type = isset($_GET['tipo']) ? sanitize_text_field($_GET['tipo']) : '';
    $ano = isset($_GET['ano']) ? sanitize_text_field($_GET['ano']) : 'todos';
    
    if (empty($export_type) || !in_array($export_type, array('pdf', 'csv', 'excel'))) {
        wp_die('Tipo de exportação inválido.');
    }
    
    $plugin = sistema_ouvidoria();
    
    try {
        // Obter dados das estatísticas
        if ($ano === 'todos') {
            $stats = $plugin->database->get_estatisticas_totais();
        } else {
            $stats = $plugin->database->get_estatisticas(intval($ano));
        }
        
        if (!$stats || !is_array($stats)) {
            wp_die('Não foi possível obter os dados das estatísticas.');
        }
        
        // Exportar de acordo com o tipo solicitado
        if ($export_type === 'pdf') {
            ouvidoria_exportar_pdf($stats, $ano);
        } elseif ($export_type === 'csv') {
            ouvidoria_exportar_csv($stats, $ano);
        } elseif ($export_type === 'excel') {
            ouvidoria_exportar_excel($stats, $ano);
        }
    } catch (Exception $e) {
        wp_die('Erro ao exportar: ' . $e->getMessage());
    }
    
    // Se chegou aqui, algo deu errado
    wp_die('Não foi possível gerar o arquivo de exportação.');
}

function ouvidoria_exportar_pdf($stats, $ano) {
    // Formatar o título com base no ano selecionado
    $titulo_ano = ($ano === 'todos') ? 'Todas as Estatísticas' : "Estatísticas do Ano $ano";
    
    // Gerar o nome do arquivo com data atual
    $data_atual = wp_date('Y-m-d_H-i');
    $filename = "estatisticas_ouvidoria_" . sanitize_title($titulo_ano) . "_" . $data_atual;
    
    // Preparar dados estatísticos para exportação
    $dados = array(
        'Título' => "Estatísticas da Ouvidoria / E-Sic - $titulo_ano",
        'Data de Geração' => wp_date('d/m/Y H:i'),
        'Resumo' => array(
            'Total de Solicitações' => $stats['total'],
            'Pendentes' => $stats['pendentes'],
            'Em Análise' => $stats['em_analise'],
            'Concluídas' => $stats['concluidas'],
            'Indeferidas' => $stats['indeferidas']
        ),
        'Por Tipo' => array(),
        'Por Identificação' => array(),
        'Por Forma de Recebimento' => array(),
        'Por Mês' => array()
    );
    
    // Formatar dados por tipo de manifestação
    foreach ($stats['por_tipo'] as $tipo => $valor) {
        $nome_tipo = ucfirst(str_replace('_', ' ', $tipo));
        $dados['Por Tipo'][$nome_tipo] = $valor->total;
    }
    
    // Formatar dados por tipo de identificação
    foreach ($stats['por_identificacao'] as $tipo => $valor) {
        $nome_tipo = ($tipo === 'identificado') ? 'Identificado' : 'Anônimo';
        $dados['Por Identificação'][$nome_tipo] = $valor->total;
    }
    
    // Formatar dados por tipo de resposta
    foreach ($stats['por_tipo_resposta'] as $tipo => $valor) {
        $nome_tipo = ($tipo === 'sistema') ? 'Sistema' : 'Presencial';
        $dados['Por Forma de Recebimento'][$nome_tipo] = $valor->total;
    }
    
    // Formatar dados por mês
    foreach ($stats['por_mes'] as $mes_key => $valor) {
        list($ano_mes, $mes_num) = explode('-', $mes_key);
        $nome_mes = wp_date('M Y', strtotime($mes_key . '-01'));
        $dados['Por Mês'][$nome_mes] = $valor->total;
    }
    
    // Verificar se a classe de PDF existe
    if (!class_exists('Ouvidoria_PDF')) {
        // Verificar se podemos incluir a classe
        $pdf_class_file = plugin_dir_path(dirname(__FILE__)) . 'includes/class-ouvidoria-pdf.php';
        if (file_exists($pdf_class_file)) {
            include_once $pdf_class_file;
        } else {
            wp_die('Classe de geração de PDF não encontrada.');
        }
    }
    
    // Gerar o PDF
    $pdf = new Ouvidoria_PDF();
    $pdf->gerar_estatisticas_pdf($dados, $filename);
    exit;
}

function ouvidoria_exportar_csv($stats, $ano) {
    // Formatar o título com base no ano selecionado
    $titulo_ano = ($ano === 'todos') ? 'Todas as Estatísticas' : "Estatísticas do Ano $ano";
    
    // Gerar o nome do arquivo com data atual
    $data_atual = wp_date('Y-m-d_H-i');
    $filename = "estatisticas_ouvidoria_" . sanitize_title($titulo_ano) . "_" . $data_atual;
    
    // Dados para exportação
    $titulo = "Estatísticas da Ouvidoria / E-Sic - $titulo_ano";
    $data_geracao = wp_date('d/m/Y H:i');
    
    // Enviar headers para download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    
    // Criar o arquivo CSV na saída
    $output = fopen('php://output', 'w');
    
    // Adicionar BOM para UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Adicionar título e data
    fputcsv($output, array($titulo));
    fputcsv($output, array('Data de Geração:', $data_geracao));
    fputcsv($output, array()); // Linha em branco
    
    // Adicionar resumo
    fputcsv($output, array('Resumo Geral'));
    fputcsv($output, array('Total de Solicitações', $stats['total']));
    fputcsv($output, array('Pendentes', $stats['pendentes']));
    fputcsv($output, array('Em Análise', $stats['em_analise']));
    fputcsv($output, array('Concluídas', $stats['concluidas']));
    fputcsv($output, array('Indeferidas', $stats['indeferidas']));
    fputcsv($output, array()); // Linha em branco
    
    // Adicionar dados por tipo
    fputcsv($output, array('Por Tipo de Manifestação'));
    foreach ($stats['por_tipo'] as $tipo => $valor) {
        $nome_tipo = ucfirst(str_replace('_', ' ', $tipo));
        fputcsv($output, array($nome_tipo, $valor->total));
    }
    fputcsv($output, array()); // Linha em branco
    
    // Adicionar dados por identificação
    fputcsv($output, array('Por Tipo de Identificação'));
    foreach ($stats['por_identificacao'] as $tipo => $valor) {
        $nome_tipo = ($tipo === 'identificado') ? 'Identificado' : 'Anônimo';
        fputcsv($output, array($nome_tipo, $valor->total));
    }
    fputcsv($output, array()); // Linha em branco
    
    // Adicionar dados por forma de recebimento
    fputcsv($output, array('Por Forma de Recebimento'));
    foreach ($stats['por_tipo_resposta'] as $tipo => $valor) {
        $nome_tipo = ($tipo === 'sistema') ? 'Sistema' : 'Presencial';
        fputcsv($output, array($nome_tipo, $valor->total));
    }
    fputcsv($output, array()); // Linha em branco
    
    // Adicionar dados por mês
    fputcsv($output, array('Por Mês'));
    foreach ($stats['por_mes'] as $mes_key => $valor) {
        list($ano_mes, $mes_num) = explode('-', $mes_key);
        $nome_mes = wp_date('M Y', strtotime($mes_key . '-01'));
        fputcsv($output, array($nome_mes, $valor->total));
    }
    
    fclose($output);
    exit;
}

function ouvidoria_exportar_excel($stats, $ano) {
    // Formatar o título com base no ano selecionado
    $titulo_ano = ($ano === 'todos') ? 'Todas as Estatísticas' : "Estatísticas do Ano $ano";
    
    // Gerar o nome do arquivo com data atual
    $data_atual = wp_date('Y-m-d_H-i');
    $filename = "estatisticas_ouvidoria_" . sanitize_title($titulo_ano) . "_" . $data_atual;
    
    // Dados para exportação
    $titulo = "Estatísticas da Ouvidoria / E-Sic - $titulo_ano";
    $data_geracao = wp_date('d/m/Y H:i');
    
    // Verificar se a biblioteca PhpSpreadsheet está disponível
    if (!class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
        // Tentar carregar via autoload
        if (file_exists(plugin_dir_path(dirname(__FILE__)) . 'vendor/autoload.php')) {
            require_once plugin_dir_path(dirname(__FILE__)) . 'vendor/autoload.php';
        } else {
            // Fallback para CSV caso o PhpSpreadsheet não esteja disponível
            ouvidoria_exportar_csv($stats, $ano);
            return;
        }
    }
    
    try {
        // Criar novo spreadsheet
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Adicionar título e data
        $sheet->setCellValue('A1', $titulo);
        $sheet->mergeCells('A1:D1');
        $sheet->setCellValue('A2', 'Data de Geração:');
        $sheet->setCellValue('B2', $data_geracao);
        
        // Estilizar título
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1:D1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        
        // Adicionar resumo
        $row = 4;
        $sheet->setCellValue('A' . $row, 'Resumo Geral');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;
        
        $sheet->setCellValue('A' . $row, 'Total de Solicitações');
        $sheet->setCellValue('B' . $row, $stats['total']);
        $row++;
        
        $sheet->setCellValue('A' . $row, 'Pendentes');
        $sheet->setCellValue('B' . $row, $stats['pendentes']);
        $row++;
        
        $sheet->setCellValue('A' . $row, 'Em Análise');
        $sheet->setCellValue('B' . $row, $stats['em_analise']);
        $row++;
        
        $sheet->setCellValue('A' . $row, 'Concluídas');
        $sheet->setCellValue('B' . $row, $stats['concluidas']);
        $row++;
        
        $sheet->setCellValue('A' . $row, 'Indeferidas');
        $sheet->setCellValue('B' . $row, $stats['indeferidas']);
        $row++;
        
        $row++; // Linha em branco
        
        // Adicionar dados por tipo
        $sheet->setCellValue('A' . $row, 'Por Tipo de Manifestação');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;
        
        foreach ($stats['por_tipo'] as $tipo => $valor) {
            $nome_tipo = ucfirst(str_replace('_', ' ', $tipo));
            $sheet->setCellValue('A' . $row, $nome_tipo);
            $sheet->setCellValue('B' . $row, $valor->total);
            $row++;
        }
        
        $row++; // Linha em branco
        
        // Adicionar dados por identificação
        $sheet->setCellValue('A' . $row, 'Por Tipo de Identificação');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;
        
        foreach ($stats['por_identificacao'] as $tipo => $valor) {
            $nome_tipo = ($tipo === 'identificado') ? 'Identificado' : 'Anônimo';
            $sheet->setCellValue('A' . $row, $nome_tipo);
            $sheet->setCellValue('B' . $row, $valor->total);
            $row++;
        }
        
        $row++; // Linha em branco
        
        // Adicionar dados por forma de recebimento
        $sheet->setCellValue('A' . $row, 'Por Forma de Recebimento');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;
        
        foreach ($stats['por_tipo_resposta'] as $tipo => $valor) {
            $nome_tipo = ($tipo === 'sistema') ? 'Sistema' : 'Presencial';
            $sheet->setCellValue('A' . $row, $nome_tipo);
            $sheet->setCellValue('B' . $row, $valor->total);
            $row++;
        }
        
        $row++; // Linha em branco
        
        // Adicionar dados por mês
        $sheet->setCellValue('A' . $row, 'Por Mês');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;
        
        foreach ($stats['por_mes'] as $mes_key => $valor) {
            list($ano_mes, $mes_num) = explode('-', $mes_key);
            $nome_mes = wp_date('M Y', strtotime($mes_key . '-01'));
            $sheet->setCellValue('A' . $row, $nome_mes);
            $sheet->setCellValue('B' . $row, $valor->total);
            $row++;
        }
        
        // Auto ajustar largura das colunas
        foreach(range('A', 'D') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        // Criar o Writer
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        
        // Configurar headers para download
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '.xlsx"');
        header('Cache-Control: max-age=0');
        
        // Salvar para saída
        $writer->save('php://output');
        exit;
        
    } catch (Exception $e) {
        // Fallback para CSV em caso de erro
        ouvidoria_exportar_csv($stats, $ano);
    }
}

add_shortcode('ouvidoria_estatisticas', 'ouvidoria_estatisticas_shortcode');