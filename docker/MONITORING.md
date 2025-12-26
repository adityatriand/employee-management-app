# Monitoring Setup Guide

This application uses Prometheus and Grafana for monitoring and observability.

## Services

### Prometheus
- **Port**: 9090
- **URL**: http://localhost:9090
- **Configuration**: `docker/prometheus/prometheus.yml`
- **Data Retention**: 30 days

### Grafana
- **Port**: 3000
- **URL**: http://localhost:3000
- **Default Credentials**:
  - Username: `admin`
  - Password: `admin123`
- **Dashboards**: Automatically provisioned from `docker/grafana/dashboards/`

## Starting the Monitoring Stack

1. Start all services including monitoring:
   ```bash
   docker-compose up -d
   ```

2. Wait for services to be healthy (check with `docker-compose ps`)

3. Access Grafana at http://localhost:3000

4. Access Prometheus at http://localhost:9090

## Metrics Endpoint

The Laravel application exposes metrics at:
- **Endpoint**: `/api/metrics`
- **Format**: Prometheus text format
- **Example**: http://localhost:8000/api/metrics

### Available Metrics

- `app_info` - Application version and environment
- `http_requests_total` - Total HTTP requests
- `database_connection_status` - Database connection health (1=up, 0=down)
- `database_queries_total` - Total database queries executed
- `database_connections_active` - Active database connections
- `cache_hits_total` - Cache hits
- `cache_misses_total` - Cache misses
- `queue_size` - Queue size (if using database queue)
- `employees_total` - Total employees count
- `users_total` - Total users count
- `workspaces_total` - Total workspaces count
- `files_total` - Total files count
- `assets_total` - Total assets count
- `active_users` - Active users in last hour
- `storage_size_bytes` - Storage directory size
- `memory_usage_bytes` - Current memory usage
- `memory_peak_bytes` - Peak memory usage
- `http_request_duration_ms` - HTTP request duration
- `system_load_1m` - System load (1 minute)
- `system_load_5m` - System load (5 minutes)
- `system_load_15m` - System load (15 minutes)

## Grafana Dashboards

### Laravel Application Metrics
A comprehensive dashboard showing:
- HTTP request metrics
- Database metrics
- Memory usage
- Application statistics
- Cache performance
- Queue status

The dashboard is automatically loaded from `docker/grafana/dashboards/laravel-app-dashboard.json`.

## Prometheus Configuration

Prometheus is configured to scrape:
1. **Prometheus itself** - Self-monitoring
2. **Laravel Application** - Metrics from `/api/metrics` endpoint
3. **MinIO** - Storage metrics (if enabled)

Scrape interval: 15 seconds

## Security Considerations

⚠️ **Important**: The metrics endpoint is currently public. For production:

1. **IP Whitelist**: Restrict access to Prometheus server IP only
2. **Authentication**: Add basic auth or token authentication
3. **Network**: Use internal Docker network for Prometheus scraping

### Recommended Production Setup

Add middleware to protect the metrics endpoint:

```php
// In routes/api.php
Route::get('/metrics', [MetricsController::class, 'index'])
    ->middleware('ip:prometheus_ip_address')
    ->name('api.metrics');
```

Or use environment-based access control in the controller.

## Troubleshooting

### Prometheus can't scrape Laravel metrics

1. Check if the app container is running: `docker-compose ps`
2. Verify metrics endpoint: `curl http://localhost:8000/api/metrics`
3. Check Prometheus targets: http://localhost:9090/targets
4. Verify network connectivity between containers

### Grafana shows "No Data"

1. Check Prometheus datasource configuration
2. Verify Prometheus is scraping metrics (check Targets page)
3. Check Grafana logs: `docker-compose logs grafana`

### Metrics not updating

1. Verify scrape interval in Prometheus config
2. Check Prometheus logs: `docker-compose logs prometheus`
3. Ensure Laravel app is receiving requests

## Custom Metrics

To add custom metrics, edit `app/Http/Controllers/MetricsController.php`:

```php
// Add your metric
$metrics[] = $this->formatMetric('your_metric_name', $value, [
    'label1' => 'value1',
]);
```

## Data Persistence

- **Prometheus data**: Stored in `prometheus_data` volume (30-day retention)
- **Grafana data**: Stored in `grafana_data` volume (dashboards, users, etc.)

To reset monitoring data:
```bash
docker-compose down -v
```

## Updating Dashboards

1. Edit dashboard JSON in `docker/grafana/dashboards/`
2. Restart Grafana: `docker-compose restart grafana`
3. Or import manually in Grafana UI

## Performance Impact

The metrics endpoint has minimal performance impact:
- Query logging is only enabled for the metrics request
- Model counts use efficient `count()` queries
- Metrics are cached where possible

For high-traffic applications, consider:
- Increasing scrape interval
- Using Prometheus exporters instead
- Implementing metric sampling

