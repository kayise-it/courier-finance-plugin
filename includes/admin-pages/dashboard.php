<?php
if (!defined('ABSPATH')) {
    exit;
}

// KPI stats
global $wpdb;
$waybills_table   = $wpdb->prefix . 'kit_waybills';
$customers_table  = $wpdb->prefix . 'kit_customers';
$deliveries_table = $wpdb->prefix . 'kit_deliveries';
$warehouse_table  = $wpdb->prefix . 'kit_warehouse_tracking';

$total_waybills   = (int)$wpdb->get_var("SELECT COUNT(*) FROM $waybills_table");
$total_customers  = (int)$wpdb->get_var("SELECT COUNT(*) FROM $customers_table");
$in_transit       = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $deliveries_table WHERE status=%s", 'in_transit'));
$warehouse_items  = (int)$wpdb->get_var("SELECT COUNT(*) FROM $warehouse_table");
$revenue_30d      = (float)$wpdb->get_var("SELECT COALESCE(SUM(product_invoice_amount),0) FROM $waybills_table WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
// Date filters
$from = isset($_GET['from']) && $_GET['from'] !== '' ? sanitize_text_field($_GET['from']) : null;
$to   = isset($_GET['to']) && $_GET['to'] !== '' ? sanitize_text_field($_GET['to']) : null;

// WHERE helpers for waybills/customers
$rangeWhere = '';
$params = [];
if ($from) { $rangeWhere .= ($rangeWhere ? ' AND ' : ' WHERE ') . 'w.created_at >= %s'; $params[] = $from . ' 00:00:00'; }
if ($to)   { $rangeWhere .= ($rangeWhere ? ' AND ' : ' WHERE ') . 'w.created_at <= %s'; $params[] = $to   . ' 23:59:59'; }

// Status mix
$scheduled_count  = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $deliveries_table WHERE status=%s", 'scheduled'));
$in_transit_count = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $deliveries_table WHERE status=%s", 'in_transit'));
$warehoused_count = (int)$wpdb->get_var("SELECT COUNT(*) FROM $warehouse_table");

// Recent waybills (last 5)
$recent_waybills_sql = "SELECT w.id, w.waybill_no, w.created_at, w.status, c.name AS customer_name
                        FROM $waybills_table w
                        LEFT JOIN $customers_table c ON c.id = w.customer_id" . $rangeWhere . " 
                        ORDER BY w.created_at DESC LIMIT 5";
$recent_waybills = $params ? $wpdb->get_results($wpdb->prepare($recent_waybills_sql, ...$params)) : $wpdb->get_results($recent_waybills_sql);

// Recent customers (last 7 days or filtered)
$customerRangeWhere = '';
$custParams = [];
if ($from || $to) {
    if ($from) { $customerRangeWhere .= ($customerRangeWhere ? ' AND ' : ' WHERE ') . 'created_at >= %s'; $custParams[] = $from . ' 00:00:00'; }
    if ($to)   { $customerRangeWhere .= ($customerRangeWhere ? ' AND ' : ' WHERE ') . 'created_at <= %s'; $custParams[] = $to   . ' 23:59:59'; }
} else {
    $customerRangeWhere = " WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
}
$recent_customers_sql = "SELECT id, name, created_at FROM $customers_table" . $customerRangeWhere . " ORDER BY created_at DESC LIMIT 5";
$recent_customers = $custParams ? $wpdb->get_results($wpdb->prepare($recent_customers_sql, ...$custParams)) : $wpdb->get_results($recent_customers_sql);
?>

<div class="wrap">
    <?= KIT_Commons::showingHeader([
        'title' => '08600 Waybills Dashboard',
        'desc'  => 'Operational overview and live logistics insights',
        'icon'  => KIT_Commons::icon('package')
    ]); ?>

    <div class="mb-6 flex flex-wrap items-center gap-3">
        <form method="get" class="flex items-center gap-2">
                        <?php foreach ($_GET as $k=>$v) { if (!in_array($k, ['from','to'])) echo '<input type="hidden" name="'.esc_attr($k).'" value="'.esc_attr($v).'">'; } ?>
            <input type="date" name="from" class="border rounded px-3 py-2" placeholder="From">
            <input type="date" name="to" class="border rounded px-3 py-2" placeholder="To">
            <?= KIT_Commons::renderButton('Apply', 'primary', 'sm', ['type'=>'submit', 'gradient'=>true]); ?>
                    </form>
        <div class="ml-auto flex items-center gap-2">
            <?= KIT_Commons::renderButton('Export CSV', 'secondary', 'sm', [
                'icon' => KIT_Commons::icon('receipt'),
                'href' => admin_url('admin-post.php?action=kit_export_waybills_csv&from=' . urlencode($from ?? '') . '&to=' . urlencode($to ?? ''))
            ]); ?>
        </div>
    </div>

    <div class="max-w-7xl mx-auto">
        <!-- Statistics Overview -->
        <div class="grid grid-cols-1 md:grid-cols-3 xl:grid-cols-6 gap-4 mb-6">
            <div class="bg-white shadow rounded-lg p-4">
                <h3 class="font-medium text-gray-500">Total Customers</h3>
                <p class="text-2xl font-bold"><?php echo number_format($total_customers); ?></p>
                    </div>
            <div class="bg-white shadow rounded-lg p-4">
                <h3 class="font-medium text-gray-500">Total Waybills</h3>
                <p class="text-2xl font-bold"><?php echo number_format($total_waybills); ?></p>
                    </div>
            <div class="bg-white shadow rounded-lg p-4">
                <h3 class="font-medium text-gray-500">Total Deliveries</h3>
                <p class="text-2xl font-bold"><?php echo number_format($in_transit + $warehouse_items); ?></p>
                </div>
            <div class="bg-white shadow rounded-lg p-4">
                <h3 class="font-medium text-gray-500">Monthly Deliveries</h3>
                <p class="text-2xl font-bold"><?php echo number_format($in_transit); ?></p>
            </div>
            <div class="bg-white shadow rounded-lg p-4">
                <h3 class="font-medium text-gray-500">Active Trucks</h3>
                <p class="text-2xl font-bold">12</p>
            </div>
            <div class="bg-white shadow rounded-lg p-4">
                <h3 class="font-medium text-gray-500">Revenue This Month</h3>
                <p class="text-2xl font-bold">R <?php echo number_format($revenue_30d, 0); ?></p>
            </div>
        </div>

        <!-- Recent Activity Section -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
            <!-- Recent Customers -->
            <div class="bg-white shadow rounded-lg p-4">
                <h3 class="font-medium text-gray-500 mb-3">Recent Customers</h3>
                <div class="space-y-2">
                    <?php if (!empty($recent_customers)): foreach ($recent_customers as $cust): ?>
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-gray-900"><?php echo htmlspecialchars($cust->name); ?></span>
                            <span class="text-xs text-gray-500"><?php echo human_time_diff(strtotime($cust->created_at), current_time('timestamp')) . ' ago'; ?></span>
                    </div>
                    <?php endforeach; else: ?>
                        <div class="text-sm text-gray-500">No customers in the last 7 days.</div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Recent Waybills -->
            <div class="bg-white shadow rounded-lg p-4">
                <h3 class="font-medium text-gray-500 mb-3">Recent Waybills</h3>
                <div class="space-y-2">
                    <?php if (!empty($recent_waybills)): foreach ($recent_waybills as $wb): ?>
                        <div class="flex justify-between items-center text-sm">
                    <div>
                                <div class="font-medium text-gray-900"><?php echo htmlspecialchars($wb->waybill_no); ?></div>
                                <div class="text-xs text-gray-500"><?php echo htmlspecialchars($wb->customer_name ?: 'Customer #'.$wb->id); ?></div>
                    </div>
                            <span class="text-xs text-gray-500"><?php echo human_time_diff(strtotime($wb->created_at), current_time('timestamp')) . ' ago'; ?></span>
                    </div>
                    <?php endforeach; else: ?>
                        <div class="text-sm text-gray-500">No waybills found.</div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Recent Left Trucks -->
            <div class="bg-white shadow rounded-lg p-4">
                <h3 class="font-medium text-gray-500 mb-3">Recent Left Trucks</h3>
                <div class="space-y-2">
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-gray-900">TR-001: JHB → CPT</span>
                        <span class="text-xs text-gray-500">5 min ago</span>
                    </div>
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-gray-900">TR-003: DBN → PTA</span>
                        <span class="text-xs text-gray-500">12 min ago</span>
                    </div>
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-gray-900">TR-007: PE → BFN</span>
                        <span class="text-xs text-gray-500">18 min ago</span>
                    </div>
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-gray-900">TR-012: EL → KIM</span>
                        <span class="text-xs text-gray-500">25 min ago</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Live Operations -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <!-- SADC Region Map -->
            <div class="bg-white shadow rounded-lg p-4">
                <h3 class="font-medium text-gray-500 mb-3">SADC Region Map</h3>
                <div id="sadcMap" style="height: 300px;" class="rounded-lg bg-gray-50 flex items-center justify-center text-gray-500">
                    Interactive Map Loading...
                </div>
            </div>

            <!-- Revenue Trends -->
            <div class="bg-white shadow rounded-lg p-4">
                <h3 class="font-medium text-gray-500 mb-3">Revenue Trends</h3>
                <div class="h-64 w-full"><canvas id="revenueChart"></canvas></div>
            </div>
        </div>

        <!-- Analytics Dashboard -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <!-- Waybill Status Distribution -->
            <div class="bg-white shadow rounded-lg p-4">
                <h3 class="font-medium text-gray-500 mb-3">Waybill Status Distribution</h3>
                <div class="h-64 w-full"><canvas id="statusPie"></canvas></div>
            </div>

            <!-- Monthly Deliveries Trend -->
            <div class="bg-white shadow rounded-lg p-4">
                <h3 class="font-medium text-gray-500 mb-3">Monthly Deliveries Trend</h3>
                <div class="h-64 w-full"><canvas id="deliveriesChart"></canvas></div>
            </div>
        </div>


 

        <!-- Quick Actions -->
        <div class="bg-white shadow rounded-lg p-4">
            <h3 class="font-medium text-gray-500 mb-3">Quick Actions</h3>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
                <a href="?page=08600-waybill-create" class="border border-gray-300 rounded-lg p-3 text-center hover:bg-gray-50 transition-colors">
                    <div class="text-sm font-medium text-gray-900">Create Waybill</div>
                </a>
                <a href="?page=add-customer" class="border border-gray-300 rounded-lg p-3 text-center hover:bg-gray-50 transition-colors">
                    <div class="text-sm font-medium text-gray-900">Add Customer</div>
                </a>
                <a href="?page=kit-deliveries" class="border border-gray-300 rounded-lg p-3 text-center hover:bg-gray-50 transition-colors">
                    <div class="text-sm font-medium text-gray-900">Schedule Delivery</div>
                </a>
                <a href="?page=kit-routes" class="border border-gray-300 rounded-lg p-3 text-center hover:bg-gray-50 transition-colors">
                    <div class="text-sm font-medium text-gray-900">Manage Routes</div>
                </a>
                <a href="?page=warehouse-tracking" class="border border-gray-300 rounded-lg p-3 text-center hover:bg-gray-50 transition-colors">
                    <div class="text-sm font-medium text-gray-900">Track Deliveries</div>
                </a>
                <a href="?page=08600-settings" class="border border-gray-300 rounded-lg p-3 text-center hover:bg-gray-50 transition-colors">
                    <div class="text-sm font-medium text-gray-900">System Settings</div>
                </a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<style>
/* Chart container enhancements - Natural sizing */
canvas {
  max-width: 100%;
  max-height: 100%;
}

/* Chart hover effects */
canvas:hover {
  cursor: crosshair;
}

/* Responsive chart adjustments */
@media (max-width: 768px) {
  .h-64 {
    height: 250px;
  }
}

/* Chart loading animation */
.chart-loading {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 100%;
  color: #6b7280;
  font-size: 14px;
}

/* Enhanced tooltip styling */
.chartjs-tooltip {
  background: rgba(0, 0, 0, 0.8) !important;
  border: 1px solid #2563eb !important;
  border-radius: 8px !important;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06) !important;
}
</style>
<script>
document.addEventListener('DOMContentLoaded', function(){
  // Check if Chart.js is loaded
  console.log('Chart.js loaded:', !!window.Chart);
  console.log('Chart version:', window.Chart ? Chart.version : 'Not available');
  
  // Test if Chart.js is available
  if (window.Chart) {
    console.log('Chart.js is available, creating charts...');
  } else {
    console.error('Chart.js is not loaded! Check the CDN link.');
  }
  
  // Function to destroy existing charts
  function destroyChart(canvasId) {
    const canvas = document.getElementById(canvasId);
    if (canvas) {
      const existingChart = Chart.getChart(canvas);
      if (existingChart) {
        existingChart.destroy();
        console.log('Destroyed existing chart:', canvasId);
      }
    }
  }
  
  // Initialize charts with proper sizing
  function initializeChart(canvasId, chartConfig) {
    console.log('Initializing chart:', canvasId);
    const canvas = document.getElementById(canvasId);
    if (!canvas) {
      console.error('Canvas not found:', canvasId);
      return null;
    }
    if (!window.Chart) {
      console.error('Chart.js not loaded');
      return null;
    }
    
    const container = canvas.parentElement;
    const containerWidth = container.clientWidth;
    const containerHeight = container.clientHeight;
    
    console.log('Container dimensions:', containerWidth, 'x', containerHeight);
    
    // Set explicit canvas dimensions
    canvas.style.width = containerWidth + 'px';
    canvas.style.height = containerHeight + 'px';
    canvas.width = containerWidth;
    canvas.height = containerHeight;
    
    // Ensure responsive and maintainAspectRatio are set
    chartConfig.options = chartConfig.options || {};
    chartConfig.options.responsive = true;
    chartConfig.options.maintainAspectRatio = false;
    
    try {
      const chart = new Chart(canvas, chartConfig);
      console.log('Chart created successfully:', canvasId);
      return chart;
    } catch (error) {
      console.error('Error creating chart:', canvasId, error);
      return null;
    }
  }

  // Revenue Trends Chart
  const revenueCtx = document.getElementById('revenueChart');
  if (revenueCtx && window.Chart) {
    console.log('Creating revenue chart...');
    destroyChart('revenueChart'); // Destroy any existing chart first
    try {
      new Chart(revenueCtx, { 
        type: 'line', 
        data: { 
          labels: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'], 
          datasets: [{ 
            label: 'Revenue (R)', 
            data: [120,150,180,160,220,260,240,300,280,310,330,360], 
            borderColor: '#2563eb', 
            backgroundColor: 'rgba(37,99,235,0.1)', 
            tension: 0.35, 
            fill: true,
            pointBackgroundColor: '#2563eb',
            pointBorderColor: '#ffffff',
            pointBorderWidth: 2,
            pointRadius: 6,
            pointHoverRadius: 8,
            borderWidth: 3
          }]
        }, 
        options: { 
          responsive: true,
          maintainAspectRatio: true,
          aspectRatio: 2,
          plugins: { 
            legend: { 
              display: true,
              position: 'top'
            },
            tooltip: {
              enabled: true,
              mode: 'index',
              intersect: false,
              backgroundColor: 'rgba(0,0,0,0.8)',
              titleColor: '#ffffff',
              bodyColor: '#ffffff',
              borderColor: '#2563eb',
              borderWidth: 1,
              cornerRadius: 8,
              displayColors: true
            }
          },
          scales: { 
            x: {
              display: true,
              title: {
                display: true,
                text: 'Month'
              }
            },
            y: { 
              beginAtZero: true,
              display: true,
              title: {
                display: true,
                text: 'Revenue (R)'
              }
            } 
          },
          interaction: {
            intersect: false,
            mode: 'index'
          }
        } 
      });
      console.log('Revenue chart created successfully!');
    } catch (error) {
      console.error('Error creating revenue chart:', error);
    }
  }

  // Status pie chart
  const statusCtx = document.getElementById('statusPie');
  if (statusCtx && window.Chart) {
    destroyChart('statusPie'); // Destroy any existing chart first
    new Chart(statusCtx, {
    type: 'doughnut',
    data: {
      labels: ['Scheduled', 'In transit', 'Warehoused'],
      datasets: [{
        data: [
          <?php echo (int)$scheduled_count; ?>,
          <?php echo (int)$in_transit_count; ?>,
          <?php echo (int)$warehoused_count; ?>
        ],
        backgroundColor: ['#6366f1', '#22c55e', '#f59e0b'],
        borderColor: '#ffffff',
        borderWidth: 2,
        hoverBorderWidth: 3,
        hoverBorderColor: '#ffffff'
      }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        aspectRatio: 1,
        plugins: { 
          legend: { 
            position: 'bottom',
            labels: {
              usePointStyle: true,
              padding: 20,
              font: {
                size: 12,
                weight: '500'
              }
            }
          },
          tooltip: {
            enabled: true,
            backgroundColor: 'rgba(0,0,0,0.8)',
            titleColor: '#ffffff',
            bodyColor: '#ffffff',
            borderColor: '#6366f1',
            borderWidth: 1,
            cornerRadius: 8,
            displayColors: true,
            callbacks: {
              title: function(context) {
                return context[0].label;
              },
              label: function(context) {
                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                const percentage = ((context.parsed / total) * 100).toFixed(1);
                return context.parsed + ' (' + percentage + '%)';
              }
            }
          }
        },
        animation: {
          duration: 2000,
          easing: 'easeInOutQuart'
        },
        cutout: '60%'
      }
    });
  }
  // Monthly Deliveries Trend Chart
  const deliveriesCtx = document.getElementById('deliveriesChart');
  if (deliveriesCtx && window.Chart) {
    destroyChart('deliveriesChart'); // Destroy any existing chart first
    new Chart(deliveriesCtx, { 
    type: 'line', 
    data: { 
      labels: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'], 
      datasets: [{ 
        label: 'Deliveries',
        data: [45,52,48,61,55,67,59,73,68,78,82,89], 
        borderColor: '#10b981',
        backgroundColor: 'rgba(16,185,129,0.1)',
        tension: .35,
        fill: true,
        pointBackgroundColor: '#10b981',
        pointBorderColor: '#ffffff',
        pointBorderWidth: 2,
        pointRadius: 6,
        pointHoverRadius: 8,
        pointHoverBackgroundColor: '#059669',
        pointHoverBorderColor: '#ffffff',
        pointHoverBorderWidth: 3,
        borderWidth: 3
      }]
    }, 
    options: {
        responsive: true,
        maintainAspectRatio: true,
        aspectRatio: 2,
        plugins: { 
          legend: { 
            display: true,
            position: 'top',
            labels: {
              usePointStyle: true,
              padding: 20,
              font: {
                size: 12,
                weight: '500'
              }
            }
          },
          tooltip: {
            enabled: true,
            mode: 'index',
            intersect: false,
            backgroundColor: 'rgba(0,0,0,0.8)',
            titleColor: '#ffffff',
            bodyColor: '#ffffff',
            borderColor: '#10b981',
            borderWidth: 1,
            cornerRadius: 8,
            displayColors: true,
            callbacks: {
              title: function(context) {
                return 'Month: ' + context[0].label;
              },
              label: function(context) {
                return 'Deliveries: ' + context.parsed.y.toLocaleString();
              },
              afterLabel: function(context) {
                const prevValue = context.dataset.data[context.dataIndex - 1];
                if (prevValue) {
                  const change = context.parsed.y - prevValue;
                  const percentChange = ((change / prevValue) * 100).toFixed(1);
                  return 'Change: ' + (change >= 0 ? '+' : '') + percentChange + '%';
                }
                return '';
              }
            }
          }
        },
        scales: { 
          x: {
            display: true,
            title: {
              display: true,
              text: 'Month',
              font: {
                size: 12,
                weight: '600'
              },
              color: '#6b7280'
            },
            grid: {
              display: true,
              color: 'rgba(107, 114, 128, 0.1)'
            },
            ticks: {
              font: {
                size: 11
              },
              color: '#6b7280'
            }
          },
          y: { 
            beginAtZero: true,
            display: true,
            title: {
              display: true,
              text: 'Number of Deliveries',
              font: {
                size: 12,
                weight: '600'
              },
              color: '#6b7280'
            },
            grid: {
              display: true,
              color: 'rgba(107, 114, 128, 0.1)'
            },
            ticks: {
              font: {
                size: 11
              },
              color: '#6b7280',
              callback: function(value) {
                return value.toLocaleString();
              }
            }
          } 
        },
        interaction: {
          intersect: false,
          mode: 'index'
        },
        animation: {
          duration: 2000,
          easing: 'easeInOutQuart'
        },
        elements: {
          line: {
            tension: 0.35
          }
        }
      } 
    });
  }
  // SADC Region Map
  const sadcMapEl = document.getElementById('sadcMap');
  if (sadcMapEl && window.L) {
    const sadcMap = L.map('sadcMap').setView([-26.2041, 28.0473], 4);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap' }).addTo(sadcMap);
    
    // SADC countries markers
    const sadcCountries = [
      { name: 'South Africa', coords: [-30.5595, 22.9375] },
      { name: 'Botswana', coords: [-22.3285, 24.6849] },
      { name: 'Namibia', coords: [-22.9576, 18.4904] },
      { name: 'Zimbabwe', coords: [-19.0154, 29.1549] },
      { name: 'Zambia', coords: [-13.1339, 27.8493] },
      { name: 'Malawi', coords: [-13.2543, 34.3015] },
      { name: 'Mozambique', coords: [-18.6657, 35.5296] },
      { name: 'Tanzania', coords: [-6.3690, 34.8888] },
      { name: 'Lesotho', coords: [-29.6100, 28.2336] },
      { name: 'Swaziland', coords: [-26.5225, 31.4659] },
      { name: 'Madagascar', coords: [-18.7669, 46.8691] },
      { name: 'Mauritius', coords: [-20.3484, 57.5522] }
    ];
    
    sadcCountries.forEach(country => {
      L.marker(country.coords).addTo(sadcMap).bindPopup(country.name);
    });
  }
  
  // Route map for mosaic layout - Tanzania focus
  const routeMapEl = document.getElementById('routeMap');
  if (routeMapEl && window.L) {
    const routeMap = L.map('routeMap').setView([-6.3690, 34.8888], 6); // Center on Tanzania
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap' }).addTo(routeMap);
    
    // 08600 HQ location (assuming Dar es Salaam, Tanzania)
    const hqLocation = [-6.7924, 39.2083]; // Dar es Salaam coordinates
    L.marker(hqLocation).addTo(routeMap).bindPopup('08600 HQ - Dar es Salaam');

    // Sample route polyline Dar es Salaam → Dodoma → Arusha
    const route = [
      [-6.7924, 39.2083],
      [-6.1620, 35.7516],
      [-3.3869, 36.6830]
    ];
    L.polyline(route, { color: '#2563eb', weight: 4 }).addTo(routeMap);
  }
  
  // Handle window resize for chart responsiveness
  let resizeTimeout;
  window.addEventListener('resize', function() {
    clearTimeout(resizeTimeout);
    resizeTimeout = setTimeout(function() {
      // Resize all charts with proper bounds
      Chart.helpers.each(Chart.instances, function(chart) {
        const canvas = chart.canvas;
        const container = canvas.parentElement;
        const containerWidth = container.clientWidth;
        const containerHeight = container.clientHeight;
        
        // Set explicit dimensions to prevent infinite growth
        canvas.style.width = containerWidth + 'px';
        canvas.style.height = containerHeight + 'px';
        canvas.width = containerWidth;
        canvas.height = containerHeight;
        
        chart.resize();
      });
    }, 250);
  });
  
  // Add loading states and error handling
  function showChartLoading(canvasId) {
    const canvas = document.getElementById(canvasId);
    if (canvas) {
      const container = canvas.parentElement;
      container.innerHTML = '<div class="chart-loading">Loading chart...</div>';
    }
  }
  
  function showChartError(canvasId, message) {
    const canvas = document.getElementById(canvasId);
    if (canvas) {
      const container = canvas.parentElement;
      container.innerHTML = '<div class="chart-loading text-red-500">Error: ' + message + '</div>';
    }
  }
});
</script>