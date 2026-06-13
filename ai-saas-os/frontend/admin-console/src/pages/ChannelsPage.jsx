import { ProTable } from '@ant-design/pro-components';
import { Typography } from 'antd';
import { statusTag, yuan } from '../utils/format.jsx';
import { useApiData } from '../hooks/useApiData.js';

export default function ChannelsPage() {
  const { data, loading } = useApiData('/admin/marketing/channels?limit=100', []);

  return (
    <>
      <Typography.Title level={3}>渠道管理</Typography.Title>
      <ProTable
        rowKey="id"
        loading={loading}
        search={false}
        options={false}
        dataSource={data}
        locale={{ emptyText: '暂无渠道' }}
        pagination={{ pageSize: 10, showSizeChanger: true }}
        columns={[
          { title: '渠道名称', dataIndex: 'name' },
          { title: '推广码', render: (_, row) => (row.promotion_links || []).map((link) => link.code).join('、') || '-' },
          { title: '推广链接', render: (_, row) => (row.promotion_links || []).map((link) => link.tracking_url).join('；') || '-' },
          { title: '订单数量', dataIndex: 'orders_count' },
          { title: '佣金金额', dataIndex: 'commission_amount_cents', render: (_, row) => yuan(row.commission_amount_cents) },
          { title: '状态', dataIndex: 'status', render: (_, row) => statusTag(row.status) },
        ]}
      />
    </>
  );
}
