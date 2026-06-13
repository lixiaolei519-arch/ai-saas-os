import { ProTable } from '@ant-design/pro-components';
import { Input, Space, Typography } from 'antd';
import { useMemo, useState } from 'react';
import { dateTime, statusTag, yuan } from '../../utils/format.jsx';
import { filterRows, tablePagination } from '../../utils/table.js';
import { useApiData } from '../../hooks/useApiData.js';

export default function PortalOrdersPage() {
  const [keyword, setKeyword] = useState('');
  const { data, loading } = useApiData('/portal/orders?per_page=100', []);
  const orders = useMemo(() => filterRows(data, keyword, (row) => `${row.order_no || ''} ${row.items?.[0]?.description || ''} ${row.status || ''}`), [data, keyword]);

  return (
    <>
      <Typography.Title level={3}>我的订单</Typography.Title>
      <Input.Search className="table-search" placeholder="搜索订单号、产品或状态" allowClear onSearch={setKeyword} onChange={(event) => setKeyword(event.target.value)} />
      <ProTable
        rowKey="id"
        loading={loading}
        search={false}
        options={false}
        dataSource={orders}
        locale={{ emptyText: '暂无订单' }}
        pagination={tablePagination()}
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
