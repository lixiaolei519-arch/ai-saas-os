import { ProTable } from '@ant-design/pro-components';
import { Input, Tag, Typography } from 'antd';
import { useMemo, useState } from 'react';
import { dateTime, statusTag } from '../utils/format.jsx';
import { filterRows, tablePagination } from '../utils/table.js';
import { useApiData } from '../hooks/useApiData.js';

export default function UsersPage() {
  const [keyword, setKeyword] = useState('');
  const { data, loading } = useApiData('/admin/users?limit=100', []);
  const users = useMemo(() => filterRows(data, keyword, (user) => `${user.name || ''} ${user.email || ''}`), [data, keyword]);

  return (
    <>
      <Typography.Title level={3}>用户管理</Typography.Title>
      <Input.Search className="table-search" placeholder="搜索姓名或邮箱" allowClear onSearch={setKeyword} onChange={(event) => setKeyword(event.target.value)} />
      <ProTable
        rowKey="id"
        loading={loading}
        search={false}
        options={false}
        dataSource={users}
        locale={{ emptyText: '暂无用户' }}
        pagination={tablePagination()}
        columns={[
          { title: '姓名', dataIndex: 'name' },
          { title: '邮箱', dataIndex: 'email', copyable: true },
          { title: '是否管理员', dataIndex: 'is_admin', render: (_, row) => row.is_admin ? <Tag color="blue">管理员</Tag> : <Tag>客户</Tag> },
          { title: '状态', dataIndex: 'status', render: (_, row) => statusTag(row.status) },
          { title: '创建时间', dataIndex: 'created_at', render: (_, row) => dateTime(row.created_at) },
        ]}
      />
    </>
  );
}
