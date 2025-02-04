<?php
function ouvidoria_estatisticas_shortcode() {
    wp_enqueue_script('chartjs', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '3.7.0', true);

    $plugin = sistema_ouvidoria();
    
    // Obter o ano atual e criar lista de anos disponíveis
    $ano_atual = isset($_GET['ano']) ? intval($_GET['ano']) : date('Y');
    
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
            <h2>Estatísticas da Ouvidoria / E-Sic</h2>
            <p>Dados atualizados em: <?php echo wp_date('d/m/Y H:i'); ?></p>

            <!-- Filtro de Ano -->
            <div class="filtro-ano">
                <label for="select-ano">Filtrar por ano:</label>
                <select id="select-ano" class="select-ano">
                    <?php foreach ($todos_anos as $ano): ?>
                        <option value="<?php echo $ano; ?>" <?php selected($ano, $ano_atual); ?>>
                            <?php echo $ano; ?> <?php echo $ano == date('Y') ? '(Atual)' : ''; ?>
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

add_shortcode('ouvidoria_estatisticas', 'ouvidoria_estatisticas_shortcode');