import { create } from 'zustand';

const tokenKey = 'ai_saas_console_token';
const userKey = 'ai_saas_console_user';

export const useAuthStore = create((set) => ({
  token: localStorage.getItem(tokenKey),
  user: JSON.parse(localStorage.getItem(userKey) || 'null'),
  setAuth: ({ token, user }) => {
    localStorage.setItem(tokenKey, token);
    localStorage.setItem(userKey, JSON.stringify(user));
    set({ token, user });
  },
  clearAuth: () => {
    localStorage.removeItem(tokenKey);
    localStorage.removeItem(userKey);
    set({ token: null, user: null });
  },
}));

export const authStorage = {
  tokenKey,
  userKey,
};
