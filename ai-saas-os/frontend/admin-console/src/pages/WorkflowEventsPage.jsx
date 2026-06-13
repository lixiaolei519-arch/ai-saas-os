import { ProTable } from '@ant-design/pro-components';
import { Input, Typography } from 'antd';
import { useMemo, useState } from 'react';
import { dateTime, statusTag } from '../utils/format.jsx';
import { filterRows, tablePagination } from '../utils/table.js';
import { useApiData } from '../hooks/useApiData.js';

export default function WorkflowEventsPage() {
  const [keyword, setKeyword] = useState('');
  const { data, loading } = useApiData('/admin/workflow-events?limit=100', []);
  const events = useMemo(
    () => filterRows(data, keyword, (row) => `${row.event_name || ''} ${row.tenant?.name || ''} ${row.status || ''}`),
    [data, keyword],
  );

  return (
    <>
      <Typography.Title level={3}>事件日志</Typography.Title>
      <Input.Search className="table-search" placeholder="搜索事件、租户或状态" allowClear onSearch={setKeyword} onChange={(event) => setKeyword(event.target.value)} />
      <ProTable
        rowKey="id"
        loading={loading}
        search={false}
        options={false}
        dataSource={events}
        locale={{ emptyText: '暂无事件日志' }}
        pagination={tablePagination()}
        columns={[
          { title: '事件', dataIndex: 'event_name' },
          { title: '租户', render: (_, row) => row.tenant?.name || row.tenant_id },
          { title: '匹配工作流', dataIndex: 'matched_workflows_count' },
          { title: '状态', render: (_, row) => statusTag(row.status) },
          { title: '发生时间', render: (_, row) => dateTime(row.occurred_at) },
        ]}
      />
    </>
  );
}
