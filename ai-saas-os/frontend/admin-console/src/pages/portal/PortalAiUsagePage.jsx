import { ProTable } from '@ant-design/pro-components';
import { Card, Col, Input, Row, Spin, Statistic, Typography } from 'antd';
import { useMemo, useState } from 'react';
import { dateTime, statusTag } from '../../utils/format.jsx';
import { filterRows, tablePagination } from '../../utils/table.js';
import { useApiData } from '../../hooks/useApiData.js';

function amount(value) {
  return `CNY ${Number(value || 0).toLocaleString('zh-CN', {
    minimumFractionDigits: 6,
    maximumFractionDigits: 6,
  })}`;
}

export default function PortalAiUsagePage() {
  const [keyword, setKeyword] = useState('');
  const { data: account, loading: accountLoading } = useApiData('/portal/ai-account', {});
  const { data, loading } = useApiData('/portal/usage-records?per_page=100', []);
  const records = useMemo(
    () => filterRows(data, keyword, (row) => `${row.request_id || ''} ${row.provider || ''} ${row.model || ''} ${row.status || ''}`),
    [data, keyword],
  );

  return (
    <Spin spinning={accountLoading}>
      <Typography.Title level={3}>AI 余额与用量</Typography.Title>
      <Row gutter={[16, 16]}>
        <Col xs={24} sm={12} lg={8}>
          <Card><Statistic title="AI 余额" value={amount(account.balance_amount)} /></Card>
        </Col>
        <Col xs={24} sm={12} lg={8}>
          <Card><Statistic title="Token 余额" value={account.balance_tokens ?? 0} /></Card>
        </Col>
        <Col xs={24} sm={12} lg={8}>
          <Card><Statistic title="账户数量" value={(account.accounts || []).length} /></Card>
        </Col>
      </Row>
      <Input.Search className="table-search" placeholder="搜索请求、Provider、模型或状态" allowClear onSearch={setKeyword} onChange={(event) => setKeyword(event.target.value)} />
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
          { title: 'Provider', dataIndex: 'provider' },
          { title: '模型', dataIndex: 'model' },
          { title: '总 Tokens', dataIndex: 'total_tokens' },
          { title: '扣费', render: (_, row) => amount(row.total_cost_amount) },
          { title: '状态', render: (_, row) => statusTag(row.status) },
          { title: '创建时间', render: (_, row) => dateTime(row.created_at) },
        ]}
      />
    </Spin>
  );
}
