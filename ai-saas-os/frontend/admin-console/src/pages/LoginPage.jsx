import { LockOutlined, UserOutlined } from '@ant-design/icons';
import { Button, Card, Form, Input, Typography } from 'antd';
import { Navigate, useNavigate } from 'react-router-dom';
import { api, errorMessage } from '../api/client.js';
import { useAuthStore } from '../store/auth.js';

export default function LoginPage() {
  const navigate = useNavigate();
  const setAuth = useAuthStore((state) => state.setAuth);
  const token = useAuthStore((state) => state.token);
  const user = useAuthStore((state) => state.user);

  if (token && user?.is_admin) return <Navigate to="/console/dashboard" replace />;
  if (token && user && !user.is_admin) return <Navigate to="/console/portal/dashboard" replace />;

  const submit = async (values) => {
    try {
      const response = await api.post('/admin/auth/login', values);
      setAuth(response.data.data);
      navigate('/console/dashboard', { replace: true });
    } catch (error) {
      errorMessage(error, '登录失败，请检查管理员账号和密码');
    }
  };

  return (
    <div className="login-page">
      <Card className="login-card">
        <Typography.Title level={2}>AI SaaS OS 控制台</Typography.Title>
        <Typography.Paragraph type="secondary">管理员登录</Typography.Paragraph>
        <Form layout="vertical" onFinish={submit} requiredMark={false}>
          <Form.Item label="邮箱" name="email" rules={[{ required: true, message: '请输入邮箱' }, { type: 'email', message: '邮箱格式不正确' }]}>
            <Input prefix={<UserOutlined />} placeholder="admin@example.com" size="large" />
          </Form.Item>
          <Form.Item label="密码" name="password" rules={[{ required: true, message: '请输入密码' }]}>
            <Input.Password prefix={<LockOutlined />} placeholder="请输入密码" size="large" />
          </Form.Item>
          <Button type="primary" htmlType="submit" size="large" block>登录</Button>
        </Form>
      </Card>
    </div>
  );
}
