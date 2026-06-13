import { CheckCircleTwoTone, CloseCircleTwoTone } from '@ant-design/icons';
import { Card, Descriptions, Spin, Typography } from 'antd';
import { useApiData } from '../hooks/useApiData.js';

function boolStatus(value) {
  return value ? <span><CheckCircleTwoTone twoToneColor="#52c41a" /> 正常</span> : <span><CloseCircleTwoTone twoToneColor="#ff4d4f" /> 异常</span>;
}

export default function SystemPage() {
  const { data, loading } = useApiData('/admin/system', {});

  return (
    <Spin spinning={loading}>
      <Typography.Title level={3}>系统状态</Typography.Title>
      <Card>
        <Descriptions bordered column={{ xs: 1, md: 2 }}>
          <Descriptions.Item label="APP_ENV">{data.app_env || '-'}</Descriptions.Item>
          <Descriptions.Item label="APP_DEBUG">{String(data.app_debug ?? '-')}</Descriptions.Item>
          <Descriptions.Item label="数据库连接状态">{boolStatus(data.database_connected)}</Descriptions.Item>
          <Descriptions.Item label="/health 状态">{boolStatus(data.health_ok)}</Descriptions.Item>
          <Descriptions.Item label="当前稳定版本">{data.stable_version || '-'}</Descriptions.Item>
          <Descriptions.Item label="当前 Git 提交">{data.git_commit || '-'}</Descriptions.Item>
          <Descriptions.Item label="PHP 版本">{data.php_version || '-'}</Descriptions.Item>
          <Descriptions.Item label="Laravel 版本">{data.laravel_version || '-'}</Descriptions.Item>
        </Descriptions>
      </Card>
    </Spin>
  );
}
