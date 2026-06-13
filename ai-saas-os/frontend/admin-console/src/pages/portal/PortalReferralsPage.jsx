import { CopyOutlined } from '@ant-design/icons';
import { Button, message, Typography } from 'antd';
import { ProTable } from '@ant-design/pro-components';
import { yuan } from '../../utils/format.jsx';
import { useApiData } from '../../hooks/useApiData.js';

async function copyLink(text) {
  await navigator.clipboard.writeText(text || '');
  message.success('推广链接已复制');
}

export default function PortalReferralsPage() {
  const { data, loading } = useApiData('/portal/referrals?per_page=100', []);

  return (
    <>
      <Typography.Title level={3}>我的推广</Typography.Title>
      <ProTable
        rowKey="id"
        loading={loading}
        search={false}
        options={false}
        dataSource={data}
        locale={{ emptyText: '暂无推广链接' }}
        pagination={{ pageSize: 10, showSizeChanger: true }}
        columns={[
          { title: '推广码', dataIndex: 'code', copyable: true },
          { title: '推广链接', dataIndex: 'tracking_url', ellipsis: true },
          { title: '归因订单数量', dataIndex: 'orders_count' },
          { title: '佣金金额', render: (_, row) => yuan(row.commission_amount_cents) },
          { title: '操作', render: (_, row) => <Button icon={<CopyOutlined />} onClick={() => copyLink(row.tracking_url)}>复制推广链接</Button> },
        ]}
      />
    </>
  );
}
