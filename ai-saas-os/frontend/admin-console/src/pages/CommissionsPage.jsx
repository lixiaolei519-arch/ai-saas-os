import { ProTable } from '@ant-design/pro-components';
import { Typography } from 'antd';
import { dateTime, statusTag, yuan } from '../utils/format.jsx';
import { useApiData } from '../hooks/useApiData.js';

export default function CommissionsPage() {
  const { data, loading } = useApiData('/admin/marketing/commissions?limit=100', []);

  return (
    <>
      <Typography.Title level={3}>佣金管理</Typography.Title>
      <ProTable
        rowKey="id"
        loading={loading}
        search={false}
        options={false}
        dataSource={data}
        locale={{ emptyText: '暂无佣金' }}
        pagination={{ pageSize: 10, showSizeChanger: true }}
        columns={[
          { title: '用户', render: (_, row) => row.tenant_id || '-' },
          { title: '渠道', dataIndex: 'marketing_channel_id' },
          { title: '订单', dataIndex: 'order_id' },
          { title: '金额', dataIndex: 'commission_amount_cents', render: (_, row) => yuan(row.commission_amount_cents) },
          { title: '状态', dataIndex: 'status', render: (_, row) => statusTag(row.status) },
          { title: '计算时间', dataIndex: 'calculated_at', render: (_, row) => dateTime(row.calculated_at) },
        ]}
      />
    </>
  );
}
