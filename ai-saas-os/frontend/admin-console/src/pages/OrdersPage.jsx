import { ProTable } from '@ant-design/pro-components';
import { Input, Space, Typography } from 'antd';
import { useMemo, useState } from 'react';
import { dateTime, statusTag, yuan } from '../utils/format.jsx';
import { useApiData } from '../hooks/useApiData.js';

export default function OrdersPage() {
  const [keyword, setKeyword] = useState('');
  const { data, loading } = useApiData('/admin/orders?limit=100', []);
  const orders = useMemo(() => data.filter((order) => {
    const owner = order.tenant?.contact_email || order.tenant?.users?.[0]?.email || '';
    const text = `${order.order_no || ''} ${owner}`;
    return text.toLowerCase().includes(keyword.toLowerCase());
  }), [data, keyword]);

  return (
    <>
      <Typography.Title level={3}>订单管理</Typography.Title>
      <Input.Search className="table-search" placeholder="搜索订单号或用户" allowClear onSearch={setKeyword} onChange={(event) => setKeyword(event.target.value)} />
      <ProTable
        rowKey="id"
        loading={loading}
        search={false}
        options={false}
        dataSource={orders}
        locale={{ emptyText: '暂无订单' }}
        pagination={{ pageSize: 10, showSizeChanger: true }}
        columns={[
          { title: '订单号', dataIndex: 'order_no', copyable: true },
          { title: '金额', dataIndex: 'total_cents', render: (_, row) => yuan(row.total_cents) },
          { title: '状态', dataIndex: 'status', render: (_, row) => statusTag(row.status) },
          { title: '用户', render: (_, row) => row.tenant?.contact_email || row.tenant?.users?.[0]?.email || '-' },
          { title: '支付状态', render: (_, row) => <Space wrap>{(row.payments || []).map((payment) => statusTag(payment.status))}</Space> },
          { title: '创建时间', dataIndex: 'created_at', render: (_, row) => dateTime(row.created_at) },
        ]}
      />
    </>
  );
}
