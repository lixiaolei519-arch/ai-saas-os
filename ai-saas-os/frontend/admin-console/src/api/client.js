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
    const status = error.response?.status;
    if (status === 401) {
      useAuthStore.getState().clearAuth();
      const loginPath = window.location.pathname.startsWith('/console/portal')
        ? '/console/portal/login'
        : '/console/login';
      if (window.location.pathname !== loginPath) {
        window.location.href = loginPath;
      }
      error.__handled = true;
    } else if (status === 403) {
      error.__handled = true;
      message.error('没有权限访问该资源');
      if (window.location.pathname.startsWith('/console') && window.location.pathname !== '/console/403') {
        window.location.href = '/console/403';
      }
    } else if (status >= 500) {
      error.__handled = true;
      message.error('服务器异常，请稍后重试');
    }
    return Promise.reject(error);
  },
);

export function errorMessage(error, fallback = '请求失败，请稍后重试') {
  if (error?.__handled) return;

  const errors = error?.response?.data?.errors;
  const firstValidationError = errors && Object.values(errors).flat().find(Boolean);
  const text = error?.response?.data?.message || firstValidationError || fallback;
  message.error(text);
}
