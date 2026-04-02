package botdb

import (
	"context"
	"encoding/json"
	"net"
	"strings"

	"github.com/redis/go-redis/v9"
)

const ValkeyKey = "botdb:ips"

// ValkeyStore wraps the in-memory Store with Valkey persistence.
type ValkeyStore struct {
	store  *Store
	vk     *redis.Client
	ctx    context.Context
}

// NewValkeyStore creates a new Persistent bot IP store.
func NewValkeyStore(client *redis.Client) (*ValkeyStore, error) {
	s := &ValkeyStore{
		store: New(),
		vk:    client,
		ctx:   context.Background(),
	}
	if err := s.loadFromValkey(); err != nil {
		return nil, err
	}
	return s, nil
}

// Contains checks the in-memory store (hot path, no Valkey hit).
func (vs *ValkeyStore) Contains(ip net.IP) bool {
	return vs.store.Contains(ip)
}

// Add appends ips and persists to Valkey.
func (vs *ValkeyStore) Add(input string) error {
	if err := vs.store.Add(input); err != nil {
		return err
	}
	return vs.saveToValkey()
}

// Exclude removes ips and persists to Valkey.
func (vs *ValkeyStore) Exclude(input string) error {
	if err := vs.store.Exclude(input); err != nil {
		return err
	}
	return vs.saveToValkey()
}

// Replace replaces all ips and persists to Valkey.
func (vs *ValkeyStore) Replace(input string) error {
	if err := vs.store.Replace(input); err != nil {
		return err
	}
	return vs.saveToValkey()
}

// Clear clears all ips and updates Valkey.
func (vs *ValkeyStore) Clear() error {
	vs.store.Clear()
	return vs.saveToValkey()
}

// Count returns the number of ranges.
func (vs *ValkeyStore) Count() int {
	return vs.store.Count()
}

// List returns the current ranges.
func (vs *ValkeyStore) List() []IPRange {
	return vs.store.List()
}

func (vs *ValkeyStore) loadFromValkey() error {
	data, err := vs.vk.Get(vs.ctx, ValkeyKey).Result()
	if err == redis.Nil {
		return nil // No key yet
	}
	if err != nil {
		return err
	}

	var ips []string
	if err := json.Unmarshal([]byte(data), &ips); err != nil {
		return err
	}

	// Bulk replace existing in-memory store with data from Valkey
	return vs.store.Replace(strings.Join(ips, "\n"))
}

func (vs *ValkeyStore) saveToValkey() error {
	ips := vs.store.StringList()
	data, err := json.Marshal(ips)
	if err != nil {
		return err
	}
	return vs.vk.Set(vs.ctx, ValkeyKey, data, 0).Err()
}
