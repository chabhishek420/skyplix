package geo

import (
	"net"
	"strings"

	"github.com/oschwald/geoip2-golang"
	"go.uber.org/zap"
)

// Result holds GeoIP lookup results for a single IP address.
type Result struct {
	CountryCode string
	City        string
	ISP         string
	ASN         uint
	ASNOrg      string
	IsDatacenter bool
}

// Resolver holds the in-memory GeoIP databases.
type Resolver struct {
	countryDB *geoip2.Reader
	cityDB    *geoip2.Reader
	asnDB     *geoip2.Reader
	logger    *zap.Logger
}

// New loads GeoIP databases from the given file paths.
func New(countryPath, cityPath, asnPath string, logger *zap.Logger) (*Resolver, error) {
	r := &Resolver{logger: logger}

	if countryPath != "" {
		db, err := geoip2.Open(countryPath)
		if err != nil {
			return nil, err
		}
		r.countryDB = db
		logger.Info("GeoIP country database loaded", zap.String("path", countryPath))
	}

	if cityPath != "" {
		db, err := geoip2.Open(cityPath)
		if err != nil {
			return nil, err
		}
		r.cityDB = db
		logger.Info("GeoIP city database loaded", zap.String("path", cityPath))
	}

	if asnPath != "" {
		db, err := geoip2.Open(asnPath)
		if err != nil {
			return nil, err
		}
		r.asnDB = db
		logger.Info("GeoIP ASN database loaded", zap.String("path", asnPath))
	}

	return r, nil
}

// Lookup resolves GeoIP data for the given IP address.
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

	if r.asnDB != nil {
		if rec, err := r.asnDB.ASN(ip); err == nil {
			result.ASN = rec.AutonomousSystemNumber
			result.ASNOrg = rec.AutonomousSystemOrganization
			result.ISP = rec.AutonomousSystemOrganization
			result.IsDatacenter = r.checkIsDatacenter(rec.AutonomousSystemOrganization)
		}
	}

	return result
}

// IsDatacenter returns true if the IP belongs to a known datacenter ASN.
func (r *Resolver) IsDatacenter(ip net.IP) bool {
	if ip == nil || ip.IsLoopback() || ip.IsPrivate() {
		return false
	}
	if r.asnDB == nil {
		return false
	}
	rec, err := r.asnDB.ASN(ip)
	if err != nil {
		return false
	}
	return r.checkIsDatacenter(rec.AutonomousSystemOrganization)
}

func (r *Resolver) checkIsDatacenter(org string) bool {
	if org == "" {
		return false
	}
	orgLower := strings.ToLower(org)
	keywords := []string{
		"amazon", "aws", "google cloud", "microsoft azure", "digitalocean",
		"linode", "vultr", "ovh", "hetzner", "contabo", "scaleway",
		"hosting", "datacenter", "data center", "cloud", "server",
		"colocation", "dedicated", "vps",
	}
	for _, kw := range keywords {
		if strings.Contains(orgLower, kw) {
			return true
		}
	}
	return false
}

// Close releases the GeoIP database file handles.
func (r *Resolver) Close() {
	if r.countryDB != nil {
		r.countryDB.Close()
	}
	if r.cityDB != nil {
		r.cityDB.Close()
	}
	if r.asnDB != nil {
		r.asnDB.Close()
	}
}
