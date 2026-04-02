package worker

import (
	"context"
	"errors"
	"sync/atomic"
	"testing"
	"time"

	"go.uber.org/zap"
)

// mockWorker is a test implementation of the Worker interface.
type mockWorker struct {
	name     string
	runFn    func(ctx context.Context) error
	startCh  chan struct{}
	finishCh chan struct{}
	started  atomic.Bool
	stopped  atomic.Bool
}

func newMockWorker(name string, runFn func(ctx context.Context) error) *mockWorker {
	return &mockWorker{
		name:     name,
		runFn:    runFn,
		startCh:  make(chan struct{}),
		finishCh: make(chan struct{}),
	}
}

func (m *mockWorker) Name() string { return m.name }

func (m *mockWorker) Run(ctx context.Context) error {
	m.started.Store(true)
	close(m.startCh) // signal that worker started

	select {
	case <-ctx.Done():
		m.stopped.Store(true)
		close(m.finishCh)
		return m.runFn(ctx)
	case <-m.finishCh:
		m.stopped.Store(true)
		return nil
	}
}

// TestManager_WaitBlocksUntilWorkersFinish verifies that Wait() blocks
// until all started workers have finished.
func TestManager_WaitBlocksUntilWorkersFinish(t *testing.T) {
	logger, _ := zap.NewDevelopment()
	ctx, cancel := context.WithCancel(context.Background())
	defer cancel()

	worker := newMockWorker("test-worker", func(ctx context.Context) error {
		<-ctx.Done()
		return ctx.Err()
	})

	mgr := NewManager(logger, worker)
	mgr.StartAll(ctx)

	// Wait for worker to start
	<-worker.startCh
	if !worker.started.Load() {
		t.Fatal("worker should have started")
	}

	// Signal worker to stop
	close(worker.finishCh)

	// Wait should complete without blocking
	done := make(chan struct{})
	go func() {
		mgr.Wait()
		close(done)
	}()

	select {
	case <-done:
		// Wait completed - good!
	case <-time.After(5 * time.Second):
		t.Fatal("Wait() did not complete within timeout")
	}

	if !worker.stopped.Load() {
		t.Error("worker should have stopped")
	}
}

// TestManager_StartAll_StartsWorkers verifies that StartAll launches
// all workers as goroutines.
func TestManager_StartAll_StartsWorkers(t *testing.T) {
	logger, _ := zap.NewDevelopment()
	ctx, cancel := context.WithCancel(context.Background())

	worker1 := newMockWorker("worker-1", func(ctx context.Context) error {
		<-ctx.Done()
		return ctx.Err()
	})

	worker2 := newMockWorker("worker-2", func(ctx context.Context) error {
		<-ctx.Done()
		return ctx.Err()
	})

	mgr := NewManager(logger, worker1, worker2)
	mgr.StartAll(ctx)

	// Wait for both workers to signal they started
	for i := 0; i < 2; {
		select {
		case <-worker1.startCh:
			i++
		case <-worker2.startCh:
			i++
		case <-time.After(2 * time.Second):
			t.Fatal("workers did not start within timeout")
		}
	}

	// Give goroutines time to complete their started.Store(true) calls
	time.Sleep(10 * time.Millisecond)

	if !worker1.started.Load() {
		t.Error("worker1 should have started")
	}
	if !worker2.started.Load() {
		t.Error("worker2 should have started")
	}

	// Clean up
	cancel()
	mgr.Wait()
}

// TestManager_WorkerError_Logged verifies that non-cancel errors from
// workers are logged without crashing.
func TestManager_WorkerError_Logged(t *testing.T) {
	logger, _ := zap.NewDevelopment()
	ctx, cancel := context.WithCancel(context.Background())
	defer cancel()

	expectedErr := errors.New("test worker error")

	worker := newMockWorker("error-worker", func(ctx context.Context) error {
		<-ctx.Done()
		return expectedErr
	})

	mgr := NewManager(logger, worker)
	mgr.StartAll(ctx)

	// Wait for worker to start
	<-worker.startCh

	// Cancel context to trigger error
	cancel()

	// Wait should complete without panic
	done := make(chan struct{})
	go func() {
		mgr.Wait()
		close(done)
	}()

	select {
	case <-done:
		// Wait completed without panic - good!
	case <-time.After(5 * time.Second):
		t.Fatal("Wait() did not complete within timeout")
	}
}

// TestManager_ContextCancel_StopsWorkers verifies that workers exit
// cleanly when context is cancelled.
func TestManager_ContextCancel_StopsWorkers(t *testing.T) {
	logger, _ := zap.NewDevelopment()
	ctx, cancel := context.WithCancel(context.Background())

	worker := newMockWorker("cancel-test-worker", func(ctx context.Context) error {
		<-ctx.Done()
		return ctx.Err()
	})

	mgr := NewManager(logger, worker)
	mgr.StartAll(ctx)

	// Wait for worker to start
	<-worker.startCh

	// Cancel context
	cancel()

	// Wait should complete
	done := make(chan struct{})
	go func() {
		mgr.Wait()
		close(done)
	}()

	select {
	case <-done:
		// Good - Wait completed
	case <-time.After(5 * time.Second):
		t.Fatal("Wait() did not complete within timeout")
	}

	if !worker.stopped.Load() {
		t.Error("worker should have stopped after context cancellation")
	}
}
