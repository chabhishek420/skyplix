package worker_test

import (
	"context"
	"errors"
	"sync/atomic"
	"testing"
	"time"

	"go.uber.org/zap"

	"github.com/skyplix/zai-tds/internal/worker"
)

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
	close(m.startCh)

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

func TestManager_WaitBlocksUntilWorkersFinish(t *testing.T) {
	logger, _ := zap.NewDevelopment()
	ctx, cancel := context.WithCancel(context.Background())
	defer cancel()

	w := newMockWorker("test-worker", func(ctx context.Context) error {
		<-ctx.Done()
		return ctx.Err()
	})

	mgr := worker.NewManager(logger, w)
	mgr.StartAll(ctx)

	<-w.startCh
	if !w.started.Load() {
		t.Fatal("worker should have started")
	}

	close(w.finishCh)

	done := make(chan struct{})
	go func() {
		mgr.Wait()
		close(done)
	}()

	select {
	case <-done:
	case <-time.After(5 * time.Second):
		t.Fatal("Wait() did not complete within timeout")
	}

	if !w.stopped.Load() {
		t.Error("worker should have stopped")
	}
}

func TestManager_StartAll_StartsWorkers(t *testing.T) {
	logger, _ := zap.NewDevelopment()
	ctx, cancel := context.WithCancel(context.Background())

	w1 := newMockWorker("worker-1", func(ctx context.Context) error {
		<-ctx.Done()
		return ctx.Err()
	})

	w2 := newMockWorker("worker-2", func(ctx context.Context) error {
		<-ctx.Done()
		return ctx.Err()
	})

	mgr := worker.NewManager(logger, w1, w2)
	mgr.StartAll(ctx)

	for i := 0; i < 2; {
		select {
		case <-w1.startCh:
			i++
		case <-w2.startCh:
			i++
		case <-time.After(2 * time.Second):
			t.Fatal("workers did not start within timeout")
		}
	}

	time.Sleep(10 * time.Millisecond)

	if !w1.started.Load() {
		t.Error("worker1 should have started")
	}
	if !w2.started.Load() {
		t.Error("worker2 should have started")
	}

	cancel()
	mgr.Wait()
}

func TestManager_WorkerError_Logged(t *testing.T) {
	logger, _ := zap.NewDevelopment()
	ctx, cancel := context.WithCancel(context.Background())
	defer cancel()

	expectedErr := errors.New("test worker error")

	w := newMockWorker("error-worker", func(ctx context.Context) error {
		<-ctx.Done()
		return expectedErr
	})

	mgr := worker.NewManager(logger, w)
	mgr.StartAll(ctx)

	<-w.startCh

	cancel()

	done := make(chan struct{})
	go func() {
		mgr.Wait()
		close(done)
	}()

	select {
	case <-done:
	case <-time.After(5 * time.Second):
		t.Fatal("Wait() did not complete within timeout")
	}
}

func TestManager_ContextCancel_StopsWorkers(t *testing.T) {
	logger, _ := zap.NewDevelopment()
	ctx, cancel := context.WithCancel(context.Background())

	w := newMockWorker("cancel-test-worker", func(ctx context.Context) error {
		<-ctx.Done()
		return ctx.Err()
	})

	mgr := worker.NewManager(logger, w)
	mgr.StartAll(ctx)

	<-w.startCh

	cancel()

	done := make(chan struct{})
	go func() {
		mgr.Wait()
		close(done)
	}()

	select {
	case <-done:
	case <-time.After(5 * time.Second):
		t.Fatal("Wait() did not complete within timeout")
	}

	if !w.stopped.Load() {
		t.Error("worker should have stopped after context cancellation")
	}
}
