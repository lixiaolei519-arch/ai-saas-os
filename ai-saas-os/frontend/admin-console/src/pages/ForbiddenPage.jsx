import { Button, Result } from 'antd';
import { useNavigate } from 'react-router-dom';
import { useAuthStore } from '../store/auth.js';

export default function ForbiddenPage() {
  const navigate = useNavigate();
  const user = useAuthStore((state) => state.user);
  const target = user?.is_admin ? '/console/dashboard' : '/console/portal/dashboard';

  return (
    <div className="result-page">
      <Result
        status="403"
        title="403"
        subTitle="当前账号没有权限访问该页面。"
        extra={<Button type="primary" onClick={() => navigate(target, { replace: true })}>返回首页</Button>}
      />
    </div>
  );
}
