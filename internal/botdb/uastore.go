package botdb

import (
	"context"
	"encoding/json"
	"strings"
	"sync"

	"github.com/redis/go-redis/v9"
)

const ValkeyUAKey = "botdb:ua_patterns"

// UAStore is a thread-safe custom User-Agent signature store with Valkey persistence.
type UAStore struct {
	mu       sync.RWMutex
	patterns []string
	vk       *redis.Client
	ctx      context.Context
}

// NewUAStore creates a new custom UA signature store.
func NewUAStore(client *redis.Client) (*UAStore, error) {
	s := &UAStore{
		patterns: make([]string, 0),
		vk:       client,
		ctx:      context.Background(),
	}
	if err := s.loadFromValkey(); err != nil {
		return nil, err
	}
	return s, nil
}

// Patterns satisfies the CustomUA interface from stage 3.
func (s *UAStore) Patterns() []string {
	s.mu.RLock()
	defer s.mu.RUnlock()
	res := make([]string, len(s.patterns))
	copy(res, s.patterns)
	return res
}

// Add appends new patterns and persists.
func (s *UAStore) Add(input string) error {
	s.mu.Lock()
	defer s.mu.Unlock()

	newPatterns := s.parseInput(input)
	// Combine, lowercase, deduplicate
	patternsMap := make(map[string]struct{})
	for _, p := range s.patterns {
		patternsMap[p] = struct{}{}
	}
	for _, p := range newPatterns {
		patternsMap[strings.ToLower(p)] = struct{}{}
	}

	merged := make([]string, 0, len(patternsMap))
	for p := range patternsMap {
		merged = append(merged, p)
	}

	s.patterns = merged
	return s.saveToValkey()
}

// Remove removes a single pattern and persists.
func (s *UAStore) Remove(pattern string) error {
	s.mu.Lock()
	defer s.mu.Unlock()

	target := strings.ToLower(pattern)
	var res []string
	for _, p := range s.patterns {
		if p != target {
			res = append(res, p)
		}
	}

	s.patterns = res
	return s.saveToValkey()
}

// Replace replaces all patterns and persists.
func (s *UAStore) Replace(input string) error {
	s.mu.Lock()
	defer s.mu.Unlock()

	s.patterns = s.parseInput(input)
	return s.saveToValkey()
}

// Clear empties the store and updates Valkey.
func (s *UAStore) Clear() error {
	s.mu.Lock()
	defer s.mu.Unlock()
	s.patterns = make([]string, 0)
	return s.saveToValkey()
}

func (s *UAStore) parseInput(input string) []string {
	fields := strings.FieldsFunc(input, func(r rune) bool {
		return r == '\n' || r == '\r' || r == ','
	})
	res := make([]string, 0, len(fields))
	for _, f := range fields {
		f = strings.TrimSpace(f)
		if f != "" {
			res = append(res, strings.ToLower(f))
		}
	}
	return res
}

func (s *UAStore) loadFromValkey() error {
	data, err := s.vk.Get(s.ctx, ValkeyUAKey).Result()
	if err == redis.Nil {
		return nil
	}
	if err != nil {
		return err
	}

	var patterns []string
	if err := json.Unmarshal([]byte(data), &patterns); err != nil {
		return err
	}
	s.patterns = patterns
	return nil
}

func (s *UAStore) saveToValkey() error {
	data, err := json.Marshal(s.patterns)
	if err != nil {
		return err
	}
	return s.vk.Set(s.ctx, ValkeyUAKey, data, 0).Err()
}
