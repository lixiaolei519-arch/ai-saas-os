import { ProTable } from '@ant-design/pro-components';
import { Input, Typography } from 'antd';
import { useMemo, useState } from 'react';
import { statusTag, yuan } from '../utils/format.jsx';
import { filterRows, tablePagination } from '../utils/table.js';
import { useApiData } from '../hooks/useApiData.js';

export default function ChannelsPage() {
  const [keyword, setKeyword] = useState('');
  const { data, loading } = useApiData('/admin/marketing/channels?limit=100', []);
  const channels = useMemo(() => filterRows(data, keyword, (channel) => {
    const links = (channel.promotion_links || []).map((link) => `${link.code || ''} ${link.tracking_url || ''}`).join(' ');
    return `${channel.name || ''} ${channel.code || ''} ${links}`;
  }), [data, keyword]);

  return (
    <>
      <Typography.Title level={3}>渠道管理</Typography.Title>
      <Input.Search className="table-search" placeholder="搜索渠道、推广码或链接" allowClear onSearch={setKeyword} onChange={(event) => setKeyword(event.target.value)} />
      <ProTable
        rowKey="id"
        loading={loading}
        search={false}
        options={false}
        dataSource={channels}
        locale={{ emptyText: '暂无渠道' }}
        pagination={tablePagination()}
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
