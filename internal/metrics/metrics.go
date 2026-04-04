package metrics

import (
	"github.com/prometheus/client_golang/prometheus"
	"github.com/prometheus/client_golang/prometheus/promauto"
)

var (
	// HTTP Metrics
	HTTPRequestsTotal = promauto.NewCounterVec(
		prometheus.CounterOpts{
			Name: "skyplix_http_requests_total",
			Help: "Total number of HTTP requests processed, partitioned by method, path, and status code.",
		},
		[]string{"method", "path", "status_code"},
	)

	HTTPRequestDuration = promauto.NewHistogramVec(
		prometheus.HistogramOpts{
			Name:    "skyplix_http_request_duration_seconds",
			Help:    "Histogram of HTTP request latencies.",
			Buckets: []float64{0.001, 0.0025, 0.005, 0.01, 0.025, 0.05, 0.1, 0.25, 0.5, 1.0},
		},
		[]string{"method", "path"},
	)

	// Pipeline Metrics
	PipelineDuration = promauto.NewHistogramVec(
		prometheus.HistogramOpts{
			Name:    "skyplix_pipeline_duration_seconds",
			Help:    "Histogram of click pipeline execution time.",
			Buckets: []float64{0.001, 0.0025, 0.005, 0.01, 0.025, 0.05, 0.1, 0.25, 0.5, 1.0},
		},
		[]string{"level"}, // "L1" or "L2"
	)

	PipelineStagesDuration = promauto.NewHistogramVec(
		prometheus.HistogramOpts{
			Name:    "skyplix_pipeline_stages_duration_seconds",
			Help:    "Histogram of per-stage pipeline execution time.",
			Buckets: []float64{0.0001, 0.0005, 0.001, 0.005, 0.01, 0.05, 0.1},
		},
		[]string{"stage"},
	)

	ClicksTotal = promauto.NewCounter(
		prometheus.CounterOpts{
			Name: "skyplix_click_requests_total",
			Help: "Total number of clicks processed.",
		},
	)

	ClicksBotTotal = promauto.NewCounter(
		prometheus.CounterOpts{
			Name: "skyplix_clicks_bot_total",
			Help: "Total number of bot clicks detected.",
		},
	)

	ClickLatency = promauto.NewHistogram(
		prometheus.HistogramOpts{
			Name:    "skyplix_click_latency_p99",
			Help:    "Latency of click processing (histogram for p99 calculation).",
			Buckets: []float64{0.001, 0.002, 0.005, 0.01, 0.025, 0.05, 0.1, 0.5},
		},
	)

	// ClickHouse Metrics
	ClickHouseFlushesTotal = promauto.NewCounterVec(
		prometheus.CounterOpts{
			Name: "skyplix_clickhouse_flushes_total",
			Help: "Total number of ClickHouse flush attempts.",
		},
		[]string{"table", "status"}, // status: "success" or "error"
	)

	ClickHouseFlushDuration = promauto.NewHistogram(
		prometheus.HistogramOpts{
			Name:    "skyplix_clickhouse_flush_duration_seconds",
			Help:    "Histogram of ClickHouse flush latencies.",
			Buckets: []float64{0.01, 0.05, 0.1, 0.25, 0.5, 1.0, 2.5, 5.0, 10.0},
		},
	)

	ClickHouseBatchSize = promauto.NewHistogram(
		prometheus.HistogramOpts{
			Name:    "skyplix_clickhouse_batch_size",
			Help:    "Histogram of records per ClickHouse flush.",
			Buckets: []float64{100, 500, 1000, 2000, 5000, 10000, 20000},
		},
	)

	ClickHouseChannelDepth = promauto.NewGaugeVec(
		prometheus.GaugeOpts{
			Name: "skyplix_clickhouse_channel_depth",
			Help: "Current depth of ClickHouse writer channels.",
		},
		[]string{"table"}, // "clicks" or "conversions"
	)

	ConvQueueDepth = promauto.NewGauge(
		prometheus.GaugeOpts{
			Name: "skyplix_conv_queue_depth",
			Help: "Current number of conversions in the channel buffer.",
		},
	)

	CacheHitsTotal = promauto.NewCounter(
		prometheus.CounterOpts{
			Name: "skyplix_cache_hits_total",
			Help: "Total number of cache hits.",
		},
	)

	CacheMissesTotal = promauto.NewCounter(
		prometheus.CounterOpts{
			Name: "skyplix_cache_misses_total",
			Help: "Total number of cache misses.",
		},
	)

	CHWriteErrorsTotal = promauto.NewCounter(
		prometheus.CounterOpts{
			Name: "skyplix_ch_write_errors_total",
			Help: "Total number of ClickHouse write errors.",
		},
	)

	// Cache Metrics
	CacheOperationsTotal = promauto.NewCounterVec(
		prometheus.CounterOpts{
			Name: "skyplix_cache_operations_total",
			Help: "Total number of cache operations.",
		},
		[]string{"operation", "status"}, // operation: "get", "set" | status: "hit", "miss", "error"
	)

	// Dependency Connection Metrics
	ActiveConnections = promauto.NewGaugeVec(
		prometheus.GaugeOpts{
			Name: "skyplix_active_connections",
			Help: "Current active connection count for external dependencies.",
		},
		[]string{"service"}, // "postgres", "valkey", "clickhouse"
	)
)
