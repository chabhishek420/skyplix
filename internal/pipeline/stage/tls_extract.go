package stage

import (
	"crypto/md5"
	"crypto/tls"
	"fmt"
	"net"
	"net/http"
	"strings"

	"github.com/skyplix/zai-tds/internal/pipeline"
)

type TLSExtractStage struct{}

func (s *TLSExtractStage) Name() string    { return "TLSExtract" }
func (s *TLSExtractStage) AlwaysRun() bool { return false }

func (s *TLSExtractStage) Process(p *pipeline.Payload) error {
	if p.RawClick == nil {
		return nil
	}

	conn := GetConnFromRequest(p.Request)
	if conn == nil {
		return nil
	}

	tlsConn, ok := conn.(*tls.Conn)
	if !ok {
		return nil
	}

	cs := tlsConn.ConnectionState()
	if !cs.HandshakeComplete {
		return nil
	}

	p.RawClick.JA3 = ja3FromConnState(cs)
	if cs.ServerName != "" {
		p.RawClick.TLSHost = cs.ServerName
	}
	p.RawClick.JA4 = ja4FromConnState(cs)

	return nil
}

func ja3FromConnState(cs tls.ConnectionState) string {
	var parts []string

	parts = append(parts, fmt.Sprintf("%d", cs.CipherSuite))
	parts = append(parts, fmt.Sprintf("%d", cs.Version))
	parts = append(parts, fmt.Sprintf("%d", cs.Version))

	parts = append(parts, "")
	parts = append(parts, "")
	parts = append(parts, "")
	parts = append(parts, "")

	return md5Hash(strings.Join(parts, ","))
}

func ja4FromConnState(cs tls.ConnectionState) string {
	tlsVersion := "13"
	switch cs.Version {
	case tls.VersionTLS10:
		tlsVersion = "10"
	case tls.VersionTLS11:
		tlsVersion = "11"
	case tls.VersionTLS12:
		tlsVersion = "12"
	}

	cipher := fmt.Sprintf("%04x", cs.CipherSuite)

	return fmt.Sprintf("t13d%s_%s", cipher, tlsVersion)
}

func md5Hash(s string) string {
	h := md5.Sum([]byte(s))
	return fmt.Sprintf("%x", h)
}

func GetConnFromRequest(r *http.Request) net.Conn {
	if r.Context().Value(http.ServerContextKey) != nil {
		return nil
	}
	if conn, ok := r.Context().Value(http.LocalAddrContextKey).(net.Conn); ok {
		return conn
	}
	return nil
}
