import { ProTable } from '@ant-design/pro-components';
import { Card, Col, Input, Row, Spin, Statistic, Table, Tag, Typography } from 'antd';
import { useMemo, useState } from 'react';
import { dateTime, statusTag } from '../utils/format.jsx';
import { filterRows, tablePagination } from '../utils/table.js';
import { useApiData } from '../hooks/useApiData.js';

function approvalTag(value) {
  return value ? <Tag color="gold">需要审批</Tag> : <Tag color="green">无需审批</Tag>;
}

function simulationTag(value) {
  return value ? <Tag color="blue">Simulation</Tag> : <Tag color="red">执行模式</Tag>;
}

function countItems(value) {
  if (Array.isArray(value)) return value.length;
  if (value && typeof value === 'object') return Object.keys(value).length;
  return 0;
}

function preview(value) {
  if (!value) return '-';
  if (typeof value === 'string') return value;
  return JSON.stringify(value);
}

function SearchableSelfEvolutionTable({
  title,
  endpoint,
  placeholder,
  emptyText,
  columns,
  searchText,
}) {
  const [keyword, setKeyword] = useState('');
  const { data, loading } = useApiData(`${endpoint}?limit=100`, []);
  const rows = useMemo(
    () => filterRows(data, keyword, searchText),
    [data, keyword, searchText],
  );

  return (
    <>
      <Typography.Title level={3}>{title}</Typography.Title>
      <Input.Search
        className="table-search"
        placeholder={placeholder}
        allowClear
        onSearch={setKeyword}
        onChange={(event) => setKeyword(event.target.value)}
      />
      <ProTable
        rowKey="id"
        loading={loading}
        search={false}
        options={false}
        dataSource={rows}
        locale={{ emptyText }}
        pagination={tablePagination()}
        columns={columns}
      />
    </>
  );
}

export function SelfEvolutionDashboardPage() {
  const { data, loading } = useApiData('/admin/self-evolution/dashboard', {});
  const cards = [
    ['扫描记录', data.scans_count],
    ['评分记录', data.scores_count],
    ['迭代计划', data.plans_count],
    ['发布评审', data.release_reviews_count],
    ['建议草案', data.suggestions_count],
    ['待审批', data.pending_approval_count],
  ];

  return (
    <Spin spinning={loading}>
      <Typography.Title level={3}>Self-Evolution Engine</Typography.Title>
      <Row gutter={[16, 16]}>
        {cards.map(([title, value]) => (
          <Col xs={24} sm={12} lg={6} key={title}>
            <Card>
              <Statistic title={title} value={value ?? 0} />
            </Card>
          </Col>
        ))}
      </Row>
      <Row gutter={[16, 16]} className="dashboard-section">
        <Col xs={24} lg={12}>
          <Card title="最新评分">
            <Table
              rowKey="name"
              size="small"
              pagination={false}
              locale={{ emptyText: '暂无评分维度' }}
              dataSource={data.latest_score?.dimensions || []}
              columns={[
                { title: '维度', dataIndex: 'name' },
                { title: '分数', dataIndex: 'score' },
              ]}
            />
          </Card>
        </Col>
        <Col xs={24} lg={12}>
          <Card title="最近建议">
            <Table
              rowKey="id"
              size="small"
              pagination={false}
              locale={{ emptyText: '暂无自进化建议' }}
              dataSource={data.recent_suggestions || []}
              columns={[
                { title: '标题', dataIndex: 'title', ellipsis: true },
                { title: '分类', dataIndex: 'category' },
                { title: '优先级', dataIndex: 'priority' },
                { title: '审批', render: (_, row) => approvalTag(row.requires_approval) },
              ]}
            />
          </Card>
        </Col>
      </Row>
    </Spin>
  );
}

export function SelfEvolutionScorePage() {
  return (
    <SearchableSelfEvolutionTable
      title="自进化评分器"
      endpoint="/admin/self-evolution/scores"
      placeholder="搜索版本、状态或建议"
      emptyText="暂无自进化评分"
      searchText={(row) => `${row.version || ''} ${row.status || ''} ${preview(row.recommendations)}`}
      columns={[
        { title: '版本', dataIndex: 'version' },
        { title: '总分', dataIndex: 'overall_score' },
        { title: '维度数', render: (_, row) => countItems(row.dimensions) },
        { title: '建议数', render: (_, row) => countItems(row.recommendations) },
        { title: '状态', render: (_, row) => statusTag(row.status) },
        { title: '模式', render: (_, row) => simulationTag(row.simulation_mode) },
        { title: '生成时间', render: (_, row) => dateTime(row.generated_at) },
      ]}
    />
  );
}

export function SelfEvolutionPlansPage() {
  return (
    <SearchableSelfEvolutionTable
      title="自进化版本规划"
      endpoint="/admin/self-evolution/plans"
      placeholder="搜索目标版本、标题或任务"
      emptyText="暂无自进化计划"
      searchText={(row) => `${row.target_version || ''} ${row.title || ''} ${preview(row.tasks)}`}
      columns={[
        { title: '目标版本', dataIndex: 'target_version' },
        { title: '标题', dataIndex: 'title', ellipsis: true },
        { title: '任务数', render: (_, row) => countItems(row.tasks) },
        { title: '审批', render: (_, row) => approvalTag(row.requires_approval) },
        { title: '状态', render: (_, row) => statusTag(row.status) },
        { title: '生成时间', render: (_, row) => dateTime(row.generated_at) },
      ]}
    />
  );
}

export function SelfEvolutionReleaseReviewPage() {
  return (
    <SearchableSelfEvolutionTable
      title="自进化发布评审"
      endpoint="/admin/self-evolution/release-reviews"
      placeholder="搜索版本、决策或建议"
      emptyText="暂无自进化发布评审"
      searchText={(row) => `${row.version || ''} ${row.decision || ''} ${preview(row.rollback_suggestions)} ${preview(row.deployment_suggestions)}`}
      columns={[
        { title: '版本', dataIndex: 'version' },
        { title: '决策', dataIndex: 'decision' },
        { title: '检查项', render: (_, row) => countItems(row.checklist) },
        { title: '回滚建议', render: (_, row) => countItems(row.rollback_suggestions) },
        { title: '部署建议', render: (_, row) => countItems(row.deployment_suggestions) },
        { title: '审批', render: (_, row) => approvalTag(row.requires_approval) },
        { title: '状态', render: (_, row) => statusTag(row.status) },
      ]}
    />
  );
}

export function SelfEvolutionSuggestionsPage() {
  return (
    <SearchableSelfEvolutionTable
      title="自进化建议中心"
      endpoint="/admin/self-evolution/suggestions"
      placeholder="搜索分类、标题、正文或优先级"
      emptyText="暂无自进化建议"
      searchText={(row) => `${row.category || ''} ${row.title || ''} ${row.body || ''} ${row.priority || ''}`}
      columns={[
        { title: '分类', dataIndex: 'category' },
        { title: '标题', dataIndex: 'title', ellipsis: true },
        { title: '优先级', dataIndex: 'priority' },
        { title: '审批', render: (_, row) => approvalTag(row.requires_approval) },
        { title: '模式', render: (_, row) => simulationTag(row.simulation_mode) },
        { title: '状态', render: (_, row) => statusTag(row.status) },
        { title: '创建时间', render: (_, row) => dateTime(row.created_at) },
      ]}
    />
  );
}
