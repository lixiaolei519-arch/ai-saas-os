import { ProTable } from '@ant-design/pro-components';
import { Input, Typography } from 'antd';
import { useMemo, useState } from 'react';
import { dateTime, statusTag, yuan } from '../../utils/format.jsx';
import { filterRows, tablePagination } from '../../utils/table.js';
import { useApiData } from '../../hooks/useApiData.js';

export default function PortalCommissionsPage() {
  const [keyword, setKeyword] = useState('');
  const { data, loading } = useApiData('/portal/commissions?per_page=100', []);
  const commissions = useMemo(() => filterRows(data, keyword, (row) => `${row.order_id || ''} ${row.status || ''}`), [data, keyword]);

  return (
    <>
      <Typography.Title level={3}>我的佣金</Typography.Title>
      <Input.Search className="table-search" placeholder="搜索订单或状态" allowClear onSearch={setKeyword} onChange={(event) => setKeyword(event.target.value)} />
      <ProTable
        rowKey="id"
        loading={loading}
        search={false}
        options={false}
        dataSource={commissions}
        locale={{ emptyText: '暂无佣金' }}
        pagination={tablePagination()}
        columns={[
          { title: '来源订单', dataIndex: 'order_id' },
          { title: '佣金金额', render: (_, row) => yuan(row.commission_amount_cents) },
          { title: '状态', render: (_, row) => statusTag(row.status) },
          { title: '创建时间', render: (_, row) => dateTime(row.created_at) },
        ]}
      />
    </>
  );
}
