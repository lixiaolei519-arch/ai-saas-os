import { CopyOutlined } from '@ant-design/icons';
import { Button, Input, message, Typography } from 'antd';
import { ProTable } from '@ant-design/pro-components';
import { useMemo, useState } from 'react';
import { yuan } from '../../utils/format.jsx';
import { filterRows, tablePagination } from '../../utils/table.js';
import { useApiData } from '../../hooks/useApiData.js';

async function copyLink(text) {
  await navigator.clipboard.writeText(text || '');
  message.success('推广链接已复制');
}

export default function PortalReferralsPage() {
  const [keyword, setKeyword] = useState('');
  const { data, loading } = useApiData('/portal/referrals?per_page=100', []);
  const referrals = useMemo(() => filterRows(data, keyword, (row) => `${row.code || ''} ${row.tracking_url || ''}`), [data, keyword]);

  return (
    <>
      <Typography.Title level={3}>我的推广</Typography.Title>
      <Input.Search className="table-search" placeholder="搜索推广码或推广链接" allowClear onSearch={setKeyword} onChange={(event) => setKeyword(event.target.value)} />
      <ProTable
        rowKey="id"
        loading={loading}
        search={false}
        options={false}
        dataSource={referrals}
        locale={{ emptyText: '暂无推广链接' }}
        pagination={tablePagination()}
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
