import { ProTable } from '@ant-design/pro-components';
import { Typography } from 'antd';
import { dateTime, statusTag } from '../utils/format.jsx';
import { useApiData } from '../hooks/useApiData.js';

export default function TenantsPage() {
  const { data, loading } = useApiData('/admin/tenants?limit=100', []);

  return (
    <>
      <Typography.Title level={3}>租户管理</Typography.Title>
      <ProTable
        rowKey="id"
        loading={loading}
        search={false}
        options={false}
        dataSource={data}
        locale={{ emptyText: '暂无租户' }}
        pagination={{ pageSize: 10, showSizeChanger: true }}
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
