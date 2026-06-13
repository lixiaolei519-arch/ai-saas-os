import {
  AppstoreOutlined,
  ApiOutlined,
  AuditOutlined,
  BankOutlined,
  DashboardOutlined,
  DollarOutlined,
  LogoutOutlined,
  RobotOutlined,
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
  { key: '/console/workflows', icon: <AppstoreOutlined />, label: '工作流' },
  { key: '/console/workflow-runs', icon: <TransactionOutlined />, label: '执行记录' },
  { key: '/console/workflow-events', icon: <AuditOutlined />, label: '事件日志' },
  { key: '/console/ai-company/dashboard', icon: <RobotOutlined />, label: 'AI Company' },
  { key: '/console/ai-company/tasks', icon: <RobotOutlined />, label: 'AI 任务池' },
  { key: '/console/ai-company/ideas', icon: <RobotOutlined />, label: 'AI 需求池' },
  { key: '/console/ai-company/roadmap', icon: <RobotOutlined />, label: 'AI 路线图' },
  { key: '/console/ai-company/releases', icon: <RobotOutlined />, label: 'AI 版本计划' },
  { key: '/console/ai-company/quality', icon: <RobotOutlined />, label: 'AI 质量评分' },
  { key: '/console/ai-company/risks', icon: <RobotOutlined />, label: 'AI 风险清单' },
  { key: '/console/ai-company/prompts', icon: <RobotOutlined />, label: 'AI 指令生成' },
  { key: '/console/ai-company/reports', icon: <RobotOutlined />, label: 'AI 运营报告' },
  { key: '/console/self-evolution/dashboard', icon: <RobotOutlined />, label: '自进化总览' },
  { key: '/console/self-evolution/score', icon: <RobotOutlined />, label: '自进化评分' },
  { key: '/console/self-evolution/plans', icon: <RobotOutlined />, label: '自进化计划' },
  { key: '/console/self-evolution/release-review', icon: <RobotOutlined />, label: '发布评审' },
  { key: '/console/self-evolution/suggestions', icon: <RobotOutlined />, label: '建议中心' },
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
