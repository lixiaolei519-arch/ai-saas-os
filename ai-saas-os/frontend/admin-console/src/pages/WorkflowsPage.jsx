import { ProTable } from '@ant-design/pro-components';
import { Input, Typography } from 'antd';
import { useMemo, useState } from 'react';
import { dateTime, statusTag } from '../utils/format.jsx';
import { filterRows, tablePagination } from '../utils/table.js';
import { useApiData } from '../hooks/useApiData.js';

export default function WorkflowsPage() {
  const [keyword, setKeyword] = useState('');
  const { data, loading } = useApiData('/admin/workflows?limit=100', []);
  const workflows = useMemo(
    () => filterRows(data, keyword, (row) => `${row.name || ''} ${row.trigger_event || ''} ${row.status || ''}`),
    [data, keyword],
  );

  return (
    <>
      <Typography.Title level={3}>工作流列表</Typography.Title>
      <Input.Search className="table-search" placeholder="搜索工作流、事件或状态" allowClear onSearch={setKeyword} onChange={(event) => setKeyword(event.target.value)} />
      <ProTable
        rowKey="id"
        loading={loading}
        search={false}
        options={false}
        dataSource={workflows}
        locale={{ emptyText: '暂无工作流' }}
        pagination={tablePagination()}
        columns={[
          { title: '名称', dataIndex: 'name' },
          { title: '触发事件', dataIndex: 'trigger_event' },
          { title: '节点数', render: (_, row) => (row.nodes || []).length },
          { title: '规则数', render: (_, row) => (row.rules || []).length },
          { title: '状态', render: (_, row) => statusTag(row.status) },
          { title: '创建时间', render: (_, row) => dateTime(row.created_at) },
        ]}
      />
    </>
  );
}
