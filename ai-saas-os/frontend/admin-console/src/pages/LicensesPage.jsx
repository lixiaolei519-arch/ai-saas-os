import { ProTable } from '@ant-design/pro-components';
import { Input, Typography } from 'antd';
import { useMemo, useState } from 'react';
import { dateTime, maskHash, statusTag } from '../utils/format.jsx';
import { useApiData } from '../hooks/useApiData.js';

export default function LicensesPage() {
  const [keyword, setKeyword] = useState('');
  const { data, loading } = useApiData('/admin/licenses?limit=100', []);
  const licenses = useMemo(() => data.filter((license) => {
    const owner = license.tenant?.contact_email || license.tenant?.users?.[0]?.email || '';
    const text = `${license.domain || ''} ${owner} ${license.metadata?.source_order_id || ''}`;
    return text.toLowerCase().includes(keyword.toLowerCase());
  }), [data, keyword]);

  return (
    <>
      <Typography.Title level={3}>授权管理</Typography.Title>
      <Input.Search className="table-search" placeholder="搜索域名、用户或订单" allowClear onSearch={setKeyword} onChange={(event) => setKeyword(event.target.value)} />
      <ProTable
        rowKey="id"
        loading={loading}
        search={false}
        options={false}
        dataSource={licenses}
        locale={{ emptyText: '暂无 License' }}
        pagination={{ pageSize: 10, showSizeChanger: true }}
        columns={[
          { title: 'LicenseKey', dataIndex: 'license_key_hash', render: (_, row) => maskHash(row.license_key_hash) },
          { title: '状态', dataIndex: 'status', render: (_, row) => statusTag(row.status) },
          { title: '到期时间', dataIndex: 'expires_at', render: (_, row) => dateTime(row.expires_at) },
          { title: '绑定域名', dataIndex: 'domain' },
          { title: '所属用户', render: (_, row) => row.tenant?.contact_email || row.tenant?.users?.[0]?.email || '-' },
          { title: '所属订单', render: (_, row) => row.metadata?.source_order_id || '-' },
        ]}
      />
    </>
  );
}
