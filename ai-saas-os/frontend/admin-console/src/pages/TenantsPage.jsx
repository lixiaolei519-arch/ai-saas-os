import { ProTable } from '@ant-design/pro-components';
import { Input, Typography } from 'antd';
import { useMemo, useState } from 'react';
import { dateTime, statusTag } from '../utils/format.jsx';
import { filterRows, tablePagination } from '../utils/table.js';
import { useApiData } from '../hooks/useApiData.js';

export default function TenantsPage() {
  const [keyword, setKeyword] = useState('');
  const { data, loading } = useApiData('/admin/tenants?limit=100', []);
  const tenants = useMemo(() => filterRows(data, keyword, (tenant) => `${tenant.name || ''} ${tenant.slug || ''} ${tenant.contact_email || ''}`), [data, keyword]);

  return (
    <>
      <Typography.Title level={3}>租户管理</Typography.Title>
      <Input.Search className="table-search" placeholder="搜索租户、Slug 或联系人" allowClear onSearch={setKeyword} onChange={(event) => setKeyword(event.target.value)} />
      <ProTable
        rowKey="id"
        loading={loading}
        search={false}
        options={false}
        dataSource={tenants}
        locale={{ emptyText: '暂无租户' }}
        pagination={tablePagination()}
        columns={[
          { title: '租户名称', dataIndex: 'name' },
          { title: 'Slug', dataIndex: 'slug', copyable: true },
          { title: '联系人', dataIndex: 'contact_email' },
          { title: '状态', dataIndex: 'status', render: (_, row) => statusTag(row.status) },
          { title: '创建时间', dataIndex: 'created_at', render: (_, row) => dateTime(row.created_at) },
        ]}
      />
    </>
  );
}
