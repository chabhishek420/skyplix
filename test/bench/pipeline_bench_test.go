package bench

import "testing"

// BenchmarkClickEndpoint is a stub benchmark for the hot path.
// Actual performance load testing and latency verification (p99 < 5ms)
// should be performed using the k6 integration tests (see test/load).
func BenchmarkClickEndpoint(b *testing.B) {
	b.Skip("Use k6 for full integration performance testing of the click pipeline.")
}
