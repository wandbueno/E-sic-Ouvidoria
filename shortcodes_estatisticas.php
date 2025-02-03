<?php
// shortcodes_estatisticas.php

function ouvidoria_estatisticas_shortcode() {
    wp_enqueue_script('chartjs', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '3.7.0', true);

    // Pega a instância do plugin
    $plugin = sistema_ouvidoria();
    
    // Usa o database do plugin para pegar todas as estatísticas
    $stats = $plugin->database->get_estatisticas();

    // Debug para ver a estrutura dos dados
    if(current_user_can('administrator')) {
        error_log('Estatísticas completas:');
        error_log(print_r($stats, true));
    }

    ob_start();
    ?>
    <div class="ouvidoria-estatisticas">
        <!-- Cards existentes -->
        <div class="ouvidoria-estatisticas">
        <!-- Restaurando os cards de estatísticas -->
        <div class="estatisticas-header">
            <h2>Estatísticas da Ouvidoria</h2>
            <p>Dados atualizados em: <?php echo date('d/m/Y H:i'); ?></p>
        </div>

        <div class="estatisticas-grid">
            <div class="card-estatistica">
                <h3>Total de Solicitações</h3>
                <div class="numero-grande">
                    <?php echo str_pad($stats['total'], 2, '0', STR_PAD_LEFT); ?>
                </div>
            </div>

            <div class="card-estatistica">
                <h3>Pendentes</h3>
                <div class="numero-grande">
                    <?php echo str_pad($stats['pendentes'], 2, '0', STR_PAD_LEFT); ?>
                </div>
            </div>

            <div class="card-estatistica">
                <h3>Em Análise</h3>
                <div class="numero-grande">
                    <?php echo str_pad($stats['em_analise'], 2, '0', STR_PAD_LEFT); ?>
                </div>
            </div>

            <div class="card-estatistica">
                <h3>Concluídas</h3>
                <div class="numero-grande">
                    <?php echo str_pad($stats['concluidas'], 2, '0', STR_PAD_LEFT); ?>
                </div>
            </div>
        </div>

        <div class="graficos-grid">
            <!-- Gráfico de Status -->
            <div class="grafico-container">
                <h3>Status das Manifestações</h3>
                <canvas id="graficoStatus"></canvas>
            </div>

            <!-- Gráfico de Tipos -->
            <div class="grafico-container">
                <h3>Tipos de Manifestações</h3>
                <canvas id="graficoTipos"></canvas>
            </div>

            <!-- Gráfico de Identificação -->
            <div class="grafico-container">
                <h3>Tipo de Identificação</h3>
                <canvas id="graficoIdentificacao"></canvas>
            </div>
			             <div class="card-estatistica">
                <h3>Tempo Médio de Resposta</h3>
                <div class="numero-grande">
                    <?php 
                    global $wpdb;
                    $table_name = $wpdb->prefix . 'ouvidoria_manifestacoes';
                    
                    // Primeiro vamos verificar se temos manifestações concluídas
                    $check = $wpdb->get_results("
                        SELECT 
                            id,
                            data_criacao,
                            data_atualizacao,
                            status,
                            TIMESTAMPDIFF(DAY, data_criacao, data_atualizacao) as dias
                        FROM {$table_name}
                        WHERE status = 'concluida'
                    ");

                    if ($check && !empty($check)) {
                        $total_dias = 0;
                        $count = 0;
                        
                        foreach ($check as $item) {
                            if ($item->dias !== null) {
                                $total_dias += $item->dias;
                                $count++;
                            }
                        }
                        
                        if ($count > 0) {
                            $media = round($total_dias / $count);
                            echo $media . ' dias';
                        } else {
                            echo 'N/A';
                        }

                        // Debug para administradores
                        if(current_user_can('administrator')) {
                            echo "<!-- \n";
                            echo "Manifestações encontradas: " . count($check) . "\n";
                            echo "Total dias: " . $total_dias . "\n";
                            echo "Contagem: " . $count . "\n";
                            foreach ($check as $item) {
                                echo "ID: {$item->id}, Criação: {$item->data_criacao}, Atualização: {$item->data_atualizacao}, Dias: {$item->dias}\n";
                            }
                            echo "\n -->";
                        }
                    } else {
                        echo 'N/A';
                        
                        // Debug
                        if(current_user_can('administrator')) {
                            echo "<!-- \n";
                            echo "Query: " . $wpdb->last_query . "\n";
                            echo "Erro: " . $wpdb->last_error . "\n";
                            echo "Nenhuma manifestação concluída encontrada\n";
                            echo "\n -->";
                        }
                    }
                    ?>
                </div>
            </div>

            <!-- Gráfico por Mês -->
            <div class="grafico-container">
                <h3>Manifestações por Mês</h3>
                <canvas id="graficoPorMes"></canvas>
            </div>
        </div>
    </div>

  <style>
	  .card-estatistica .numero-grande {
    font-size: 2em;
    font-weight: bold;
    color: #0073aa;
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

    .estatisticas-grid {
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
        text-align: center;
    }

    .numero-grande {
        font-size: 2.5em;
        font-weight: bold;
        color: #0073aa;
    }

    .graficos-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
    }

    .grafico-container {
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    </style>
    <script>
    jQuery(document).ready(function($) {
        // Gráfico de Status
        new Chart(document.getElementById('graficoStatus'), {
            type: 'doughnut',
            data: {
                labels: ['Pendentes', 'Em Análise', 'Concluídas'],
                datasets: [{
                    data: [
                        <?php echo $stats['pendentes']; ?>,
                        <?php echo $stats['em_analise']; ?>,
                        <?php echo $stats['concluidas']; ?>
                    ],
                    backgroundColor: ['#FFA500', '#3498db', '#2ecc71']
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'bottom' } }
            }
        });

        // Gráfico de Tipos
        const tiposData = {
            labels: [],
            datasets: [{
                data: [],
                backgroundColor: []
            }]
        };

        <?php if (!empty($stats['por_tipo'])): ?>
            tiposData.labels = Object.keys(<?php echo json_encode($stats['por_tipo']); ?>);
            tiposData.datasets[0].data = Object.values(<?php echo json_encode($stats['por_tipo']); ?>).map(item => item.total);
            tiposData.datasets[0].backgroundColor = ['#9b59b6', '#e74c3c', '#f1c40f', '#2ecc71', '#3498db'];
        <?php endif; ?>

        new Chart(document.getElementById('graficoTipos'), {
            type: 'pie',
            data: tiposData,
            options: {
                responsive: true,
                plugins: { legend: { position: 'bottom' } }
            }
        });

        // Gráfico de Identificação
        const identificacaoData = {
            labels: [],
            datasets: [{
                data: [],
                backgroundColor: ['#3498db', '#95a5a6']
            }]
        };

        <?php if (!empty($stats['por_identificacao'])): ?>
            identificacaoData.labels = Object.keys(<?php echo json_encode($stats['por_identificacao']); ?>);
            identificacaoData.datasets[0].data = Object.values(<?php echo json_encode($stats['por_identificacao']); ?>).map(item => item.total);
        <?php endif; ?>

        new Chart(document.getElementById('graficoIdentificacao'), {
            type: 'pie',
            data: identificacaoData,
            options: {
                responsive: true,
                plugins: { legend: { position: 'bottom' } }
            }
        });

        // Gráfico por Mês
        const porMesData = {
            labels: [],
            datasets: [{
                label: 'Manifestações',
                data: [],
                backgroundColor: '#3498db'
            }]
        };

        <?php if (!empty($stats['por_mes'])): ?>
            porMesData.labels = Object.keys(<?php echo json_encode($stats['por_mes']); ?>).map(date => {
                return new Date(date).toLocaleDateString('pt-BR', {month: 'short', year: 'numeric'});
            });
            porMesData.datasets[0].data = Object.values(<?php echo json_encode($stats['por_mes']); ?>).map(item => item.total);
        <?php endif; ?>

        new Chart(document.getElementById('graficoPorMes'), {
            type: 'bar',
            data: porMesData,
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    }
                },
                plugins: { legend: { display: false } }
            }
        });
    });
    </script>
    <?php
    return ob_get_clean();
}

add_shortcode('ouvidoria_estatisticas', 'ouvidoria_estatisticas_shortcode');