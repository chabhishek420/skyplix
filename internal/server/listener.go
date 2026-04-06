package server

import (
	"net"
	"sync"

	"github.com/psanford/tlsfingerprint"
)

// fingerprintListener wraps a net.Listener to extract TLS fingerprints from incoming connections.
type fingerprintListener struct {
	net.Listener
	mu           sync.RWMutex
	fingerprints map[string]*tlsfingerprint.Fingerprint
}

func newFingerprintListener(l net.Listener) *fingerprintListener {
	return &fingerprintListener{
		Listener:     l,
		fingerprints: make(map[string]*tlsfingerprint.Fingerprint),
	}
}

func (l *fingerprintListener) Accept() (net.Conn, error) {
	conn, err := l.Listener.Accept()
	if err != nil {
		return nil, err
	}

	fp, wrappedConn, err := tlsfingerprint.FingerprintConn(conn)
	addr := wrappedConn.RemoteAddr().String()

	if err == nil && fp != nil {
		l.mu.Lock()
		l.fingerprints[addr] = fp
		l.mu.Unlock()
	}

	return &connWrapper{
		Conn: wrappedConn,
		onClose: func() {
			l.Cleanup(addr)
		},
	}, nil
}

type connWrapper struct {
	net.Conn
	onClose func()
}

func (c *connWrapper) Close() error {
	if c.onClose != nil {
		c.onClose()
	}
	return c.Conn.Close()
}

func (l *fingerprintListener) GetFingerprint(remoteAddr string) *tlsfingerprint.Fingerprint {
	l.mu.RLock()
	defer l.mu.RUnlock()
	return l.fingerprints[remoteAddr]
}

func (l *fingerprintListener) Cleanup(remoteAddr string) {
	l.mu.Lock()
	defer l.mu.Unlock()
	delete(l.fingerprints, remoteAddr)
}
