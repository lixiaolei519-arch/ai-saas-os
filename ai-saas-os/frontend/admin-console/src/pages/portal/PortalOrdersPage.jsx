import { ProTable } from '@ant-design/pro-components';
import { Space, Typography } from 'antd';
import { dateTime, statusTag, yuan } from '../../utils/format.jsx';
import { useApiData } from '../../hooks/useApiData.js';

export default function PortalOrdersPage() {
  const { data, loading } = useApiData('/portal/orders?per_page=100', []);

  return (
    <>
      <Typography.Title level={3}>我的订单</Typography.Title>
      <ProTable
        rowKey="id"
        loading={loading}
        search={false}
        options={false}
        dataSource={data}
        locale={{ emptyText: '暂无订单' }}
        pagination={{ pageSize: 10, showSizeChanger: true }}
        columns={[
          { title: '订单号', dataIndex: 'order_no', copyable: true },
          { title: '产品名称', render: (_, row) => row.items?.[0]?.description || '-' },
          { title: '金额', render: (_, row) => yuan(row.total_cents) },
          { title: '支付状态', render: (_, row) => <Space wrap>{(row.payments || []).map((payment) => statusTag(payment.status))}</Space> },
          { title: '订单状态', render: (_, row) => statusTag(row.status) },
          { title: '创建时间', render: (_, row) => dateTime(row.created_at) },
        ]}
      />
    </>
  );
}
