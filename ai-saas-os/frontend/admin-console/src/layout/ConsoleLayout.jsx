import {
  AppstoreOutlined,
  ApiOutlined,
  AuditOutlined,
  BankOutlined,
  DashboardOutlined,
  DollarOutlined,
  LogoutOutlined,
  SafetyCertificateOutlined,
  SettingOutlined,
  TransactionOutlined,
  UserOutlined,
} from '@ant-design/icons';
import { Layout, Menu, Typography, Button, Space } from 'antd';
import { Outlet, useLocation, useNavigate } from 'react-router-dom';
import { useAuthStore } from '../store/auth.js';
import { useApiData } from '../hooks/useApiData.js';
import AppMeta from '../components/AppMeta.jsx';
import AuthStatus from '../components/AuthStatus.jsx';

const { Header, Sider, Content } = Layout;

const menuItems = [
  { key: '/console/dashboard', icon: <DashboardOutlined />, label: '首页' },
  { key: '/console/users', icon: <UserOutlined />, label: '用户管理' },
  { key: '/console/tenants', icon: <BankOutlined />, label: '租户管理' },
  { key: '/console/licenses', icon: <SafetyCertificateOutlined />, label: '授权管理' },
  { key: '/console/orders', icon: <TransactionOutlined />, label: '订单管理' },
  { key: '/console/payments', icon: <AuditOutlined />, label: '支付回调' },
  { key: '/console/channels', icon: <AppstoreOutlined />, label: '渠道管理' },
  { key: '/console/commissions', icon: <DollarOutlined />, label: '佣金管理' },
  { key: '/console/ai-usage', icon: <ApiOutlined />, label: 'AI 用量' },
  { key: '/console/plugins', icon: <AppstoreOutlined />, label: '插件交付' },
  { key: '/console/plugin-downloads', icon: <AuditOutlined />, label: '插件下载' },
  { key: '/console/system', icon: <SettingOutlined />, label: '系统状态' },
];

export default function ConsoleLayout() {
  const navigate = useNavigate();
  const location = useLocation();
  const user = useAuthStore((state) => state.user);
  const clearAuth = useAuthStore((state) => state.clearAuth);
  const { data: system } = useApiData('/admin/system', {});

  const logout = () => {
    clearAuth();
    navigate('/console/login', { replace: true });
  };

  return (
    <Layout className="console-shell">
      <Sider breakpoint="lg" collapsedWidth="0" width={248} className="console-sider">
        <div className="console-brand">AI SaaS OS</div>
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
          <Typography.Title level={4} className="console-title">企业级控制台</Typography.Title>
          <Space className="console-header-right" size={16} wrap>
            <AppMeta system={system} />
            <AuthStatus user={user} fallback="管理员" />
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
