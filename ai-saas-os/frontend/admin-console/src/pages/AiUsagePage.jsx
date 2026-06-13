import { ProTable } from '@ant-design/pro-components';
import { Card, Col, Input, Row, Statistic, Typography } from 'antd';
import { useMemo, useState } from 'react';
import { dateTime, statusTag } from '../utils/format.jsx';
import { filterRows, tablePagination } from '../utils/table.js';
import { useApiData } from '../hooks/useApiData.js';

function amount(value) {
  return `CNY ${Number(value || 0).toLocaleString('zh-CN', {
    minimumFractionDigits: 6,
    maximumFractionDigits: 6,
  })}`;
}

export default function AiUsagePage() {
  const [keyword, setKeyword] = useState('');
  const { data, loading } = useApiData('/admin/ai/usage-records?limit=100', []);
  const records = useMemo(
    () => filterRows(data, keyword, (row) => `${row.request_id || ''} ${row.provider || ''} ${row.model || ''} ${row.tenant?.name || ''} ${row.status || ''}`),
    [data, keyword],
  );
  const totalTokens = records.reduce((sum, row) => sum + Number(row.total_tokens || 0), 0);
  const totalCost = records.reduce((sum, row) => sum + Number(row.total_cost_amount || 0), 0);

  return (
    <>
      <Typography.Title level={3}>AI 用量记录</Typography.Title>
      <Row gutter={[16, 16]} className="dashboard-section">
        <Col xs={24} sm={12} lg={8}>
          <Card><Statistic title="记录数" value={records.length} /></Card>
        </Col>
        <Col xs={24} sm={12} lg={8}>
          <Card><Statistic title="Token 消耗" value={totalTokens} /></Card>
        </Col>
        <Col xs={24} sm={12} lg={8}>
          <Card><Statistic title="扣费金额" value={amount(totalCost)} /></Card>
        </Col>
      </Row>
      <Input.Search className="table-search" placeholder="搜索请求、模型、租户或状态" allowClear onSearch={setKeyword} onChange={(event) => setKeyword(event.target.value)} />
      <ProTable
        rowKey="id"
        loading={loading}
        search={false}
        options={false}
        dataSource={records}
        locale={{ emptyText: '暂无 AI 用量记录' }}
        pagination={tablePagination()}
        columns={[
          { title: '请求 ID', dataIndex: 'request_id', copyable: true, ellipsis: true },
          { title: '租户', render: (_, row) => row.tenant?.name || row.tenant_id },
          { title: '用户', render: (_, row) => row.user?.email || row.user_id || '-' },
          { title: 'Provider', dataIndex: 'provider' },
          { title: '模型', dataIndex: 'model' },
          { title: 'Prompt Tokens', dataIndex: 'prompt_tokens' },
          { title: 'Completion Tokens', dataIndex: 'completion_tokens' },
          { title: '总 Tokens', dataIndex: 'total_tokens' },
          { title: '扣费', render: (_, row) => amount(row.total_cost_amount) },
          { title: '状态', render: (_, row) => statusTag(row.status) },
          { title: '创建时间', render: (_, row) => dateTime(row.created_at) },
        ]}
      />
    </>
  );
}
