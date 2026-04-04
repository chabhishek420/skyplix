# Configuration Reference

SkyPlix uses a `config.yaml` file on startup. Environment variables can explicitly override ANY nested key in the YAML file by utilizing their flat structure.

## `config.yaml` Example
```yaml
server:
  host: "0.0.0.0"
  port: 8080

postgres:
  dsn: "postgres://zai:zai-pass@127.0.0.1:5432/zai_tds?sslmode=disable"

valkey:
  addr: "127.0.0.1:6379"
  password: ""
  db: 0

clickhouse:
  addr: "127.0.0.1:9000"
  database: "zai_analytics"
  username: "default"
  password: ""

geoip:
  country_db: "/var/lib/geoip/GeoLite2-Country.mmdb"
  city_db: "/var/lib/geoip/GeoLite2-City.mmdb"
  asn_db: "/var/lib/geoip/GeoLite2-ASN.mmdb"

system:
  salt: "change-me-in-production-min-32-chars"
  debug: false
  log_level: "info"
  rate_limit_per_ip: 200
  rate_limit_window: "1m"
```

## Environment Overrides
- `SERVER_PORT`: Overrides `server.port`
- `DATABASE_URL`: Overrides `postgres.dsn`
- `VALKEY_URL`: Overrides `valkey.addr`
- `CLICKHOUSE_URL`: Overrides `clickhouse.addr`
- `SYSTEM_SALT`: Overrides `system.salt`
- `DEBUG`: Set to `1` or `true` to enable debug mode
- `LOG_LEVEL`: String, e.g., "debug", "info", "warn", "error"
