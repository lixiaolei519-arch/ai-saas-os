import axios from 'axios';
import { message } from 'antd';
import { useAuthStore } from '../store/auth.js';

export const apiBaseURL = import.meta.env.VITE_API_BASE_URL || '/api/v1';

export const api = axios.create({
  baseURL: apiBaseURL,
  headers: {
    Accept: 'application/json',
  },
});

api.interceptors.request.use((config) => {
  const token = useAuthStore.getState().token;
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  config.headers.Accept = 'application/json';
  return config;
});

api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      useAuthStore.getState().clearAuth();
      if (!window.location.pathname.endsWith('/console/login')) {
        window.location.href = '/console/login';
      }
    }
    return Promise.reject(error);
  },
);

export function errorMessage(error, fallback = '请求失败，请稍后重试') {
  const text = error?.response?.data?.message || error?.response?.data?.errors?.email?.[0] || fallback;
  message.error(text);
}
