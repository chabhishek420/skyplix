import axios from 'axios';

let authToken: string | null = null;

export const setAuthToken = (token: string | null) => {
  authToken = token;
  if (!token) {
    window.dispatchEvent(new Event('auth-unauthorized'));
  }
};

export const getAuthToken = () => authToken;

export const api = axios.create({
  baseURL: '/api/v1',
  headers: {
    'Content-Type': 'application/json',
  },
});

api.interceptors.request.use(
  (config) => {
    if (authToken && config.headers) {
      config.headers['Authorization'] = `Bearer ${authToken}`;
    }
    return config;
  },
  (error) => Promise.reject(error)
);

api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response && error.response.status === 401) {
      authToken = null;
      window.dispatchEvent(new Event('auth-unauthorized'));
    }
    return Promise.reject(error);
  }
);
