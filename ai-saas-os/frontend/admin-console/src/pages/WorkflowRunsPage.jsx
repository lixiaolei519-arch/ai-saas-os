import { ProTable } from '@ant-design/pro-components';
import { Input, Typography } from 'antd';
import { useMemo, useState } from 'react';
import { dateTime, statusTag } from '../utils/format.jsx';
import { filterRows, tablePagination } from '../utils/table.js';
import { useApiData } from '../hooks/useApiData.js';

export default function WorkflowRunsPage() {
  const [keyword, setKeyword] = useState('');
  const { data, loading } = useApiData('/admin/workflow-runs?limit=100', []);
  const runs = useMemo(
    () => filterRows(data, keyword, (row) => `${row.workflow_definition?.name || ''} ${row.trigger_event || ''} ${row.status || ''}`),
    [data, keyword],
  );

  return (
    <>
      <Typography.Title level={3}>执行记录</Typography.Title>
      <Input.Search className="table-search" placeholder="搜索工作流、事件或状态" allowClear onSearch={setKeyword} onChange={(event) => setKeyword(event.target.value)} />
      <ProTable
        rowKey="id"
        loading={loading}
        search={false}
        options={false}
        dataSource={runs}
        locale={{ emptyText: '暂无执行记录' }}
        pagination={tablePagination()}
        columns={[
          { title: '工作流', render: (_, row) => row.workflow_definition?.name || row.workflow_definition_id },
          { title: '租户', render: (_, row) => row.tenant?.name || row.tenant_id },
          { title: '触发事件', dataIndex: 'trigger_event' },
          { title: '步骤数', render: (_, row) => (row.steps || []).length },
          { title: '状态', render: (_, row) => statusTag(row.status) },
          { title: '开始时间', render: (_, row) => dateTime(row.started_at) },
          { title: '结束时间', render: (_, row) => dateTime(row.finished_at) },
        ]}
      />
    </>
  );
}
