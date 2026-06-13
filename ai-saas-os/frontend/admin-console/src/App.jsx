import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom';
import { useAuthStore } from './store/auth.js';
import ConsoleLayout from './layout/ConsoleLayout.jsx';
import PortalLayout from './layout/PortalLayout.jsx';
import LoginPage from './pages/LoginPage.jsx';
import PortalLoginPage from './pages/PortalLoginPage.jsx';
import ForbiddenPage from './pages/ForbiddenPage.jsx';
import NotFoundPage from './pages/NotFoundPage.jsx';
import DashboardPage from './pages/DashboardPage.jsx';
import UsersPage from './pages/UsersPage.jsx';
import TenantsPage from './pages/TenantsPage.jsx';
import LicensesPage from './pages/LicensesPage.jsx';
import OrdersPage from './pages/OrdersPage.jsx';
import PaymentsPage from './pages/PaymentsPage.jsx';
import ChannelsPage from './pages/ChannelsPage.jsx';
import CommissionsPage from './pages/CommissionsPage.jsx';
import SystemPage from './pages/SystemPage.jsx';
import AiUsagePage from './pages/AiUsagePage.jsx';
import PluginsPage from './pages/PluginsPage.jsx';
import PluginDownloadsPage from './pages/PluginDownloadsPage.jsx';
import WorkflowsPage from './pages/WorkflowsPage.jsx';
import WorkflowRunsPage from './pages/WorkflowRunsPage.jsx';
import WorkflowEventsPage from './pages/WorkflowEventsPage.jsx';
import PortalDashboardPage from './pages/portal/PortalDashboardPage.jsx';
import PortalLicensesPage from './pages/portal/PortalLicensesPage.jsx';
import PortalOrdersPage from './pages/portal/PortalOrdersPage.jsx';
import PortalReferralsPage from './pages/portal/PortalReferralsPage.jsx';
import PortalCommissionsPage from './pages/portal/PortalCommissionsPage.jsx';
import PortalAiUsagePage from './pages/portal/PortalAiUsagePage.jsx';
import PortalPluginsPage from './pages/portal/PortalPluginsPage.jsx';

function AdminProtected({ children }) {
  const token = useAuthStore((state) => state.token);
  const user = useAuthStore((state) => state.user);
  if (!token) return <Navigate to="/console/login" replace />;
  if (!user) return <Navigate to="/console/login" replace />;
  if (!user.is_admin) return <Navigate to="/console/403" replace />;
  return children;
}

function PortalProtected({ children }) {
  const token = useAuthStore((state) => state.token);
  const user = useAuthStore((state) => state.user);
  if (!token) return <Navigate to="/console/portal/login" replace />;
  if (!user) return <Navigate to="/console/portal/login" replace />;
  if (user.is_admin) return <Navigate to="/console/403" replace />;
  return children;
}

export default function App() {
  return (
    <BrowserRouter>
      <Routes>
        <Route path="/console/login" element={<LoginPage />} />
        <Route path="/console/portal/login" element={<PortalLoginPage />} />
        <Route path="/console/403" element={<ForbiddenPage />} />
        <Route path="/console/admin" element={<Navigate to="/console/dashboard" replace />} />
        <Route
          path="/console"
          element={(
            <AdminProtected>
              <ConsoleLayout />
            </AdminProtected>
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
          <Route path="ai-usage" element={<AiUsagePage />} />
          <Route path="plugins" element={<PluginsPage />} />
          <Route path="plugin-downloads" element={<PluginDownloadsPage />} />
          <Route path="workflows" element={<WorkflowsPage />} />
          <Route path="workflow-runs" element={<WorkflowRunsPage />} />
          <Route path="workflow-events" element={<WorkflowEventsPage />} />
          <Route path="system" element={<SystemPage />} />
          <Route path="*" element={<NotFoundPage />} />
        </Route>
        <Route
          path="/console/portal"
          element={(
            <PortalProtected>
              <PortalLayout />
            </PortalProtected>
          )}
        >
          <Route index element={<Navigate to="/console/portal/dashboard" replace />} />
          <Route path="dashboard" element={<PortalDashboardPage />} />
          <Route path="licenses" element={<PortalLicensesPage />} />
          <Route path="orders" element={<PortalOrdersPage />} />
          <Route path="referrals" element={<PortalReferralsPage />} />
          <Route path="commissions" element={<PortalCommissionsPage />} />
          <Route path="ai-usage" element={<PortalAiUsagePage />} />
          <Route path="plugins" element={<PortalPluginsPage />} />
          <Route path="*" element={<NotFoundPage />} />
        </Route>
        <Route path="*" element={<NotFoundPage />} />
      </Routes>
    </BrowserRouter>
  );
}
