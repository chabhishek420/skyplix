import http from 'k6/http';
import { check } from 'k6';

export const options = {
  discardResponseBodies: true,
  scenarios: {
    sustained: {
      executor: 'constant-arrival-rate',
      rate: 1000,
      timeUnit: '1s',
      duration: '30s',
      preAllocatedVUs: 100,
      maxVUs: 500,
    },
    spike: {
      executor: 'ramping-arrival-rate',
      startRate: 1000,
      timeUnit: '1s',
      preAllocatedVUs: 500,
      maxVUs: 2000,
      stages: [
        { target: 1000, duration: '10s' },
        { target: 5000, duration: '10s' }, // Spike to 5k RPS
        { target: 1000, duration: '10s' },
      ],
      startTime: '30s', // runs after sustained
    },
  },
  thresholds: {
    // We expect 99% of requests to complete within 5ms during sustained load
    'http_req_duration{scenario:sustained}': ['p(99)<5'],
    // During spikes, we relax the requirement to 10ms
    'http_req_duration{scenario:spike}': ['p(99)<10'],
    // Overall rate limit or routing failures should be < 1%
    'http_req_failed': ['rate<0.01'],
  },
};

export default function () {
  // Exclude following redirects to measure pure API ingest latency
  const params = {
    redirects: 0,
    tags: { name: 'RawClick' },
  };
  
  // Hit a non-existent alias to stress routing, IP limits, and DB fallbacks
  const res = http.get('http://localhost:8080/load_test_alias', params);

  check(res, {
    // Expect 404 since it's a dummy alias without data, 
    // or 429 if rate limiter triggers
    'status is 404 or 429': (r) => r.status === 404 || r.status === 429,
  });
}
