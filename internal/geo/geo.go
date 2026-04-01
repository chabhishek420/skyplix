package geo

import (
	"net"

	"github.com/oschwald/geoip2-golang"
	"go.uber.org/zap"
)

// Result holds GeoIP lookup results for a single IP address.
type Result struct {
	CountryCode string
	City        string
	ISP         string
}

// Resolver holds the in-memory GeoIP databases.
// Both databases are loaded once at startup — zero disk I/O on hot path.
type Resolver struct {
	countryDB *geoip2.Reader
	cityDB    *geoip2.Reader
	logger    *zap.Logger
}

// New loads GeoIP databases from the given file paths.
// Either path can be empty — that database is skipped with a warning.
func New(countryPath, cityPath string, logger *zap.Logger) (*Resolver, error) {
	r := &Resolver{logger: logger}

	if countryPath != "" {
		db, err := geoip2.Open(countryPath)
		if err != nil {
			return nil, err
		}
		r.countryDB = db
		logger.Info("GeoIP country database loaded", zap.String("path", countryPath))
	} else {
		logger.Warn("GeoIP country database not configured — country_code will be empty")
	}

	if cityPath != "" {
		db, err := geoip2.Open(cityPath)
		if err != nil {
			return nil, err
		}
		r.cityDB = db
		logger.Info("GeoIP city database loaded", zap.String("path", cityPath))
	} else {
		logger.Warn("GeoIP city database not configured — city will be empty")
	}

	return r, nil
}

// Lookup resolves GeoIP data for the given IP address.
// Returns empty Result if databases are not loaded or IP is private/loopback.
func (r *Resolver) Lookup(ip net.IP) Result {
	if ip == nil || ip.IsLoopback() || ip.IsPrivate() {
		return Result{}
	}

	var result Result

	if r.countryDB != nil {
		if rec, err := r.countryDB.Country(ip); err == nil {
			result.CountryCode = rec.Country.IsoCode
		}
	}

	if r.cityDB != nil {
		if rec, err := r.cityDB.City(ip); err == nil {
			if result.CountryCode == "" {
				result.CountryCode = rec.Country.IsoCode
			}
			if len(rec.City.Names) > 0 {
				result.City = rec.City.Names["en"]
			}
		}
	}

	return result
}

// Close releases the GeoIP database file handles.
func (r *Resolver) Close() {
	if r.countryDB != nil {
		r.countryDB.Close()
	}
	if r.cityDB != nil {
		r.cityDB.Close()
	}
}
