package worker

import (
	"context"
	"errors"
	"sync"

	"go.uber.org/zap"
)

// Worker is the interface for all background goroutines.
type Worker interface {
	Name() string
	Run(ctx context.Context) error
}

// Manager runs and supervises a set of Workers.
type Manager struct {
	workers []Worker
	logger  *zap.Logger
	wg      sync.WaitGroup
}

// NewManager creates a Manager with the given workers.
func NewManager(logger *zap.Logger, workers ...Worker) *Manager {
	return &Manager{
		workers: workers,
		logger:  logger,
	}
}

// StartAll launches all workers as goroutines.
// Workers run until ctx is cancelled.
// Panics and unexpected errors are logged but do not crash the process.
func (m *Manager) StartAll(ctx context.Context) {
	for _, w := range m.workers {
		w := w // capture loop variable
		m.wg.Add(1)
		go func() {
			defer m.wg.Done()
			m.logger.Info("worker started", zap.String("worker", w.Name()))
			if err := w.Run(ctx); err != nil && !errors.Is(err, context.Canceled) {
				m.logger.Error("worker exited with error",
					zap.String("worker", w.Name()),
					zap.Error(err),
				)
			} else {
				m.logger.Info("worker stopped", zap.String("worker", w.Name()))
			}
		}()
	}
}

// Wait blocks until all started workers have finished.
func (m *Manager) Wait() {
	m.wg.Wait()
}
