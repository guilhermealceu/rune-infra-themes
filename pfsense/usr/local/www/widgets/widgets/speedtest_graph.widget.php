<?php
require_once("guiconfig.inc");

$data = [];
$file = "/root/speedtest-data.csv";
$max_points = 12;

if (file_exists($file)) {
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $lines = array_slice($lines, 1); // Remove header
    $lines = array_slice($lines, -$max_points); // Get last N entries
    
    foreach ($lines as $line) {
        $fields = str_getcsv($line);
        if (count($fields) >= 4) {
            $data[] = [
                'timestamp' => date("H:i", strtotime($fields[0])),
                'download' => (float)$fields[1],
                'upload' => (float)$fields[2],
                'ping' => (float)$fields[3]
            ];
        }
    }
}

$latest = !empty($data) ? end($data) : null;
?>

<div class="speed-chart-widget">
    <div class="chart-header">
        <div class="chart-title">Velocidade da Internet</div>
        <div class="chart-legend">
            <span class="legend-download">↓ Download</span>
            <span class="legend-upload">↑ Upload</span>
        </div>
    </div>
    
    <div class="chart-container">
        <canvas id="speedChart"></canvas>
    </div>
    
    <?php if ($latest): ?>
    <div class="current-speeds">
        <div class="current-speed download">
            <span class="speed-value"><?= round($latest['download'], 1) ?></span>
            <span class="speed-unit">Mbps</span>
            <span class="speed-label">Download</span>
        </div>
        <div class="current-speed upload">
            <span class="speed-value"><?= round($latest['upload'], 1) ?></span>
            <span class="speed-unit">Mbps</span>
            <span class="speed-label">Upload</span>
        </div>
        <div class="current-speed ping">
            <span class="speed-value"><?= round($latest['ping'], 1) ?></span>
            <span class="speed-unit">ms</span>
            <span class="speed-label">Ping</span>
        </div>
    </div>
    <?php endif; ?>
    
   <div class="chart-footer">
    Último teste: <?= $latest ? date("d/m H:i", strtotime(str_getcsv($lines[count($lines)-1])[0])) : 'N/A' ?>
</div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('speedChart').getContext('2d');
    
    const speedChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: [<?= implode(',', array_map(function($item) { return "'" . $item['timestamp'] . "'"; }, $data)) ?>],
            datasets: [
                {
                    label: 'Download',
                    data: [<?= implode(',', array_column($data, 'download')) ?>],
                    borderColor: '#4285F4',
                    backgroundColor: 'rgba(66, 133, 244, 0.1)',
                    borderWidth: 2,
                    tension: 0.3,
                    fill: true
                },
                {
                    label: 'Upload',
                    data: [<?= implode(',', array_column($data, 'upload')) ?>],
                    borderColor: '#34A853',
                    backgroundColor: 'rgba(52, 168, 83, 0.1)',
                    borderWidth: 2,
                    tension: 0.3,
                    fill: true
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    mode: 'index',
                    intersect: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    },
                    ticks: {
                        callback: function(value) {
                            return value + ' Mbps';
                        }
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            },
            interaction: {
                mode: 'nearest',
                axis: 'x',
                intersect: false
            }
        }
    });
});
</script>

<style>
.speed-chart-widget {
    font-family: 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
    padding: 15px;
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.chart-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.chart-title {
    font-weight: 600;
    font-size: 14px;
    color: #333;
}

.chart-legend {
    font-size: 12px;
}

.legend-download {
    color: #4285F4;
    margin-right: 10px;
}

.legend-upload {
    color: #34A853;
}

.chart-container {
    height: 200px;
    margin: 10px 0;
}

.current-speeds {
    display: flex;
    justify-content: space-around;
    margin: 15px 0;
    gap: 10px;
}

.current-speed {
    text-align: center;
    padding: 10px;
    border-radius: 8px;
    background: #f8f9fa;
    flex: 1;
}

.current-speed.download {
    border-bottom: 3px solid #4285F4;
}

.current-speed.upload {
    border-bottom: 3px solid #34A853;
}

.current-speed.ping {
    border-bottom: 3px solid #EA4335;
}

.speed-value {
    font-size: 18px;
    font-weight: 600;
    display: block;
}

.speed-unit {
    font-size: 12px;
    color: #888;
}

.speed-label {
    font-size: 11px;
    color: #666;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: block;
    margin-top: 3px;
}

.chart-footer {
    font-size: 11px;
    color: #888;
    text-align: right;
    padding-top: 8px;
    border-top: 1px solid #eee;
}
</style>
