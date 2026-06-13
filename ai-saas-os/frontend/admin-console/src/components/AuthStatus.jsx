import { TeamOutlined, UserOutlined } from '@ant-design/icons';
import { Space, Tag, Typography } from 'antd';

export default function AuthStatus({ user, fallback }) {
  const isAdmin = Boolean(user?.is_admin);

  return (
    <Space size={8} wrap className="auth-status">
      {isAdmin ? <TeamOutlined /> : <UserOutlined />}
      <Typography.Text>{user?.email || fallback}</Typography.Text>
      <Tag color={isAdmin ? 'blue' : 'green'}>{isAdmin ? '管理员' : '客户'}</Tag>
    </Space>
  );
}
