import { Button, Result } from 'antd';
import { useNavigate } from 'react-router-dom';
import { useAuthStore } from '../store/auth.js';

export default function NotFoundPage() {
  const navigate = useNavigate();
  const user = useAuthStore((state) => state.user);
  const target = user?.is_admin ? '/console/dashboard' : '/console/portal/dashboard';

  return (
    <div className="result-page">
      <Result
        status="404"
        title="404"
        subTitle="页面不存在或已移动。"
        extra={<Button type="primary" onClick={() => navigate(target, { replace: true })}>返回首页</Button>}
      />
    </div>
  );
}
