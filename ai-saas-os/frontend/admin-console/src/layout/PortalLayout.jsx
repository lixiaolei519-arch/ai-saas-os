import {
  ApiOutlined,
  AppstoreOutlined,
  DashboardOutlined,
  DollarOutlined,
  LinkOutlined,
  LogoutOutlined,
  SafetyCertificateOutlined,
  ShoppingCartOutlined,
} from '@ant-design/icons';
import { Button, Layout, Menu, Space, Typography } from 'antd';
import { Outlet, useLocation, useNavigate } from 'react-router-dom';
import { useAuthStore } from '../store/auth.js';
import AppMeta from '../components/AppMeta.jsx';
import AuthStatus from '../components/AuthStatus.jsx';

const { Header, Sider, Content } = Layout;

const menuItems = [
  { key: '/console/portal/dashboard', icon: <DashboardOutlined />, label: '客户首页' },
  { key: '/console/portal/licenses', icon: <SafetyCertificateOutlined />, label: '我的授权' },
  { key: '/console/portal/orders', icon: <ShoppingCartOutlined />, label: '我的订单' },
  { key: '/console/portal/referrals', icon: <LinkOutlined />, label: '我的推广' },
  { key: '/console/portal/commissions', icon: <DollarOutlined />, label: '我的佣金' },
  { key: '/console/portal/ai-usage', icon: <ApiOutlined />, label: 'AI 余额' },
  { key: '/console/portal/plugins', icon: <AppstoreOutlined />, label: '我的插件' },
];

export default function PortalLayout() {
  const navigate = useNavigate();
  const location = useLocation();
  const user = useAuthStore((state) => state.user);
  const clearAuth = useAuthStore((state) => state.clearAuth);

  const logout = () => {
    clearAuth();
    navigate('/console/portal/login', { replace: true });
  };

  return (
    <Layout className="console-shell">
      <Sider breakpoint="lg" collapsedWidth="0" width={248} className="portal-sider">
        <div className="console-brand">客户门户</div>
        <Menu
          theme="dark"
          mode="inline"
          selectedKeys={[location.pathname]}
          items={menuItems}
          onClick={({ key }) => navigate(key)}
        />
      </Sider>
      <Layout>
        <Header className="console-header">
          <Typography.Title level={4} className="console-title">客户门户</Typography.Title>
          <Space className="console-header-right" size={16} wrap>
            <AppMeta />
            <AuthStatus user={user} fallback="客户" />
            <Button icon={<LogoutOutlined />} onClick={logout}>退出</Button>
          </Space>
        </Header>
        <Content className="console-content">
          <Outlet />
        </Content>
      </Layout>
    </Layout>
  );
}
