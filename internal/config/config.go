package config

import (
	"fmt"
	"os"
	"time"

	"gopkg.in/yaml.v3"
)

// Config holds all application configuration.
// Environment variables override YAML values (see Load).
type Config struct {
	Server     ServerConfig     `yaml:"server"`
	Postgres   PostgresConfig   `yaml:"postgres"`
	Valkey     ValkeyConfig     `yaml:"valkey"`
	ClickHouse ClickHouseConfig `yaml:"clickhouse"`
	GeoIP      GeoIPConfig      `yaml:"geoip"`
	System     SystemConfig     `yaml:"system"`
}

type ServerConfig struct {
	Host string `yaml:"host"`
	Port int    `yaml:"port"`
}

type PostgresConfig struct {
	DSN string `yaml:"dsn"`
}

type ValkeyConfig struct {
	Addr     string `yaml:"addr"`
	Password string `yaml:"password"`
	DB       int    `yaml:"db"`
}

type ClickHouseConfig struct {
	Addr     string `yaml:"addr"`
	Database string `yaml:"database"`
	Username string `yaml:"username"`
	Password string `yaml:"password"`
}

type GeoIPConfig struct {
	CountryDB string `yaml:"country_db"`
	CityDB    string `yaml:"city_db"`
	ASNDB     string `yaml:"asn_db"`
}

type SystemConfig struct {
	Salt     string `yaml:"salt"`
	Debug           bool          `yaml:"debug"`
	LogLevel        string        `yaml:"log_level"`
	RateLimitPerIP  int           `yaml:"rate_limit_per_ip"`
	RateLimitWindow time.Duration `yaml:"rate_limit_window"`
}

// Load reads config from yamlPath and applies environment variable overrides.
// Environment variables take precedence over YAML values.
func Load(yamlPath string) (*Config, error) {
	cfg := defaults()

	data, err := os.ReadFile(yamlPath)
	if err != nil {
		if !os.IsNotExist(err) {
			return nil, fmt.Errorf("read config file: %w", err)
		}
		// Config file is optional if env vars are set
	} else {
		if err := yaml.Unmarshal(data, cfg); err != nil {
			return nil, fmt.Errorf("parse config yaml: %w", err)
		}
	}

	// Environment variable overrides
	if v := os.Getenv("SERVER_HOST"); v != "" {
		cfg.Server.Host = v
	}
	if v := os.Getenv("SERVER_PORT"); v != "" {
		fmt.Sscanf(v, "%d", &cfg.Server.Port)
	}
	if v := os.Getenv("DATABASE_URL"); v != "" {
		cfg.Postgres.DSN = v
	}
	if v := os.Getenv("VALKEY_URL"); v != "" {
		cfg.Valkey.Addr = v
	}
	if v := os.Getenv("CLICKHOUSE_URL"); v != "" {
		cfg.ClickHouse.Addr = v
	}
	if v := os.Getenv("SYSTEM_SALT"); v != "" {
		cfg.System.Salt = v
	}
	if v := os.Getenv("DEBUG"); v == "true" || v == "1" {
		cfg.System.Debug = true
	}
	if v := os.Getenv("LOG_LEVEL"); v != "" {
		cfg.System.LogLevel = v
	}

	if err := cfg.validate(); err != nil {
		return nil, fmt.Errorf("config validation: %w", err)
	}

	return cfg, nil
}

func (c *Config) Addr() string {
	return fmt.Sprintf("%s:%d", c.Server.Host, c.Server.Port)
}

func defaults() *Config {
	return &Config{
		Server: ServerConfig{
			Host: "0.0.0.0",
			Port: 8080,
		},
		System: SystemConfig{
			LogLevel:        "info",
			Debug:           false,
			RateLimitPerIP:  60,
			RateLimitWindow: time.Minute,
		},
		Valkey: ValkeyConfig{
			DB: 0,
		},
		ClickHouse: ClickHouseConfig{
			Database: "zai_analytics",
			Username: "default",
		},
	}
}

func (c *Config) validate() error {
	if c.Server.Port < 1 || c.Server.Port > 65535 {
		return fmt.Errorf("server.port must be between 1 and 65535, got %d", c.Server.Port)
	}
	if c.Postgres.DSN == "" {
		return fmt.Errorf("postgres.dsn is required (or set DATABASE_URL env var)")
	}
	if c.Valkey.Addr == "" {
		return fmt.Errorf("valkey.addr is required (or set VALKEY_URL env var)")
	}
	if c.System.Salt == "" || c.System.Salt == "change-me-in-production-min-32-chars" {
		if !c.System.Debug {
			return fmt.Errorf("system.salt must be set to a secure value in production")
		}
	}
	if c.System.RateLimitPerIP < 1 {
		return fmt.Errorf("system.rate_limit_per_ip must be >= 1, got %d", c.System.RateLimitPerIP)
	}
	if c.System.RateLimitWindow < time.Second {
		return fmt.Errorf("system.rate_limit_window must be at least 1s, got %v", c.System.RateLimitWindow)
	}
	return nil
}

// Warnings returns a list of non-fatal configuration issues or sub-optimal settings.
func (c *Config) Warnings() []string {
	var warnings []string

	if c.System.Debug {
		warnings = append(warnings, "system.debug is ENABLED (do not use in production)")
	}
	if c.System.Salt == "" || c.System.Salt == "change-me-in-production-min-32-chars" {
		warnings = append(warnings, "system.salt is using default/insecure value (unsafe if debug is disabled)")
	}
	if c.ClickHouse.Addr == "" {
		warnings = append(warnings, "clickhouse.addr is missing; postback analytics and raw click streaming are disabled")
	}
	if c.GeoIP.CountryDB == "" || c.GeoIP.CityDB == "" || c.GeoIP.ASNDB == "" {
		warnings = append(warnings, "geoip databases are not fully configured; geographic reporting will be degraded")
	}

	return warnings
}
