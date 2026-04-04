# SkyPlix Load Testing

We use [k6](https://k6.io/) to verify the `<5ms` p99 latency requirement across the click pipeline.

## Execution

Ensure the SkyPlix server is running (with Valkey/Redis running):
```bash
go run cmd/zai-tds/main.go
```

Run the tests using the k6 CLI:
```bash
k6 run test/load/click_pipeline.js
```

## Thresholds
- **Sustained Load (1K RPS)**: p(99) latency < 5ms.
- **Spike Load (5K RPS)**: p(99) latency < 10ms.
- **Failure Rate**: < 1%.
