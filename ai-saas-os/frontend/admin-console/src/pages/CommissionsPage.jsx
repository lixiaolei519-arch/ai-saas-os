import { ProTable } from '@ant-design/pro-components';
import { Input, Typography } from 'antd';
import { useMemo, useState } from 'react';
import { dateTime, statusTag, yuan } from '../utils/format.jsx';
import { filterRows, tablePagination } from '../utils/table.js';
import { useApiData } from '../hooks/useApiData.js';

export default function CommissionsPage() {
  const [keyword, setKeyword] = useState('');
  const { data, loading } = useApiData('/admin/marketing/commissions?limit=100', []);
  const commissions = useMemo(() => filterRows(data, keyword, (row) => `${row.tenant_id || ''} ${row.marketing_channel_id || ''} ${row.order_id || ''} ${row.status || ''}`), [data, keyword]);

  return (
    <>
      <Typography.Title level={3}>佣金管理</Typography.Title>
      <Input.Search className="table-search" placeholder="搜索用户、渠道、订单或状态" allowClear onSearch={setKeyword} onChange={(event) => setKeyword(event.target.value)} />
      <ProTable
        rowKey="id"
        loading={loading}
        search={false}
        options={false}
        dataSource={commissions}
        locale={{ emptyText: '暂无佣金' }}
        pagination={tablePagination()}
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
