import { CopyOutlined } from '@ant-design/icons';
import { Button, message, Space, Tag, Typography } from 'antd';
import { ProTable } from '@ant-design/pro-components';
import dayjs from 'dayjs';
import { dateTime } from '../../utils/format.jsx';
import { useApiData } from '../../hooks/useApiData.js';

function licenseStatus(row) {
  if (row.status !== 'active') return <Tag color="red">禁用</Tag>;
  if (row.expires_at && dayjs(row.expires_at).isBefore(dayjs())) return <Tag color="orange">过期</Tag>;
  return <Tag color="green">有效</Tag>;
}

async function copyText(text) {
  await navigator.clipboard.writeText(text || '');
  message.success('已复制');
}

export default function PortalLicensesPage() {
  const { data, loading } = useApiData('/portal/licenses?per_page=100', []);

  return (
    <>
      <Typography.Title level={3}>我的授权</Typography.Title>
      <ProTable
        rowKey="id"
        loading={loading}
        search={false}
        options={false}
        dataSource={data}
        locale={{ emptyText: '暂无授权' }}
        pagination={{ pageSize: 10, showSizeChanger: true }}
        columns={[
          { title: 'LicenseKey', dataIndex: 'license_key', copyable: true, ellipsis: true },
          { title: '状态', render: (_, row) => licenseStatus(row) },
          { title: '到期时间', render: (_, row) => dateTime(row.expires_at) },
          { title: '绑定域名', dataIndex: 'domain' },
          { title: '所属订单', render: (_, row) => row.source_order_id || '-' },
          {
            title: '操作',
            render: (_, row) => (
              <Space>
                <Button icon={<CopyOutlined />} onClick={() => copyText(row.license_key)}>复制 LicenseKey</Button>
              </Space>
            ),
          },
        ]}
      />
    </>
  );
}
