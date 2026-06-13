import { ProTable } from '@ant-design/pro-components';
import { Input, Typography } from 'antd';
import { useMemo, useState } from 'react';
import { dateTime, statusTag } from '../utils/format.jsx';
import { filterRows, tablePagination } from '../utils/table.js';
import { useApiData } from '../hooks/useApiData.js';

export default function PaymentsPage() {
  const [keyword, setKeyword] = useState('');
  const { data, loading } = useApiData('/admin/payment-callbacks?limit=100', []);
  const callbacks = useMemo(() => filterRows(data, keyword, (row) => `${row.payment?.order?.order_no || ''} ${row.out_trade_no || ''} ${row.channel || ''} ${row.status || ''}`), [data, keyword]);

  return (
    <>
      <Typography.Title level={3}>支付回调</Typography.Title>
      <Input.Search className="table-search" placeholder="搜索订单号、渠道或状态" allowClear onSearch={setKeyword} onChange={(event) => setKeyword(event.target.value)} />
      <ProTable
        rowKey="id"
        loading={loading}
        search={false}
        options={false}
        dataSource={callbacks}
        locale={{ emptyText: '暂无支付回调' }}
        pagination={tablePagination()}
        columns={[
          { title: '订单号', render: (_, row) => row.payment?.order?.order_no || row.out_trade_no || '-' },
          { title: '回调状态', dataIndex: 'status', render: (_, row) => statusTag(row.status) },
          { title: '支付渠道', dataIndex: 'channel' },
          { title: '签名', dataIndex: 'signature_valid', render: (_, row) => row.signature_valid ? statusTag('processed') : statusTag('failed') },
          { title: '创建时间', dataIndex: 'created_at', render: (_, row) => dateTime(row.created_at) },
        ]}
      />
    </>
  );
}
