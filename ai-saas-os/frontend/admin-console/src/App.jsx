import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom';
import { useAuthStore } from './store/auth.js';
import ConsoleLayout from './layout/ConsoleLayout.jsx';
import LoginPage from './pages/LoginPage.jsx';
import DashboardPage from './pages/DashboardPage.jsx';
import UsersPage from './pages/UsersPage.jsx';
import TenantsPage from './pages/TenantsPage.jsx';
import LicensesPage from './pages/LicensesPage.jsx';
import OrdersPage from './pages/OrdersPage.jsx';
import PaymentsPage from './pages/PaymentsPage.jsx';
import ChannelsPage from './pages/ChannelsPage.jsx';
import CommissionsPage from './pages/CommissionsPage.jsx';
import SystemPage from './pages/SystemPage.jsx';

function Protected({ children }) {
  const token = useAuthStore((state) => state.token);
  return token ? children : <Navigate to="/console/login" replace />;
}

export default function App() {
  return (
    <BrowserRouter>
      <Routes>
        <Route path="/console/login" element={<LoginPage />} />
        <Route
          path="/console"
          element={(
            <Protected>
              <ConsoleLayout />
            </Protected>
          )}
        >
          <Route index element={<Navigate to="/console/dashboard" replace />} />
          <Route path="dashboard" element={<DashboardPage />} />
          <Route path="users" element={<UsersPage />} />
          <Route path="tenants" element={<TenantsPage />} />
          <Route path="licenses" element={<LicensesPage />} />
          <Route path="orders" element={<OrdersPage />} />
          <Route path="payments" element={<PaymentsPage />} />
          <Route path="channels" element={<ChannelsPage />} />
          <Route path="commissions" element={<CommissionsPage />} />
          <Route path="system" element={<SystemPage />} />
        </Route>
        <Route path="*" element={<Navigate to="/console/dashboard" replace />} />
      </Routes>
    </BrowserRouter>
  );
}
