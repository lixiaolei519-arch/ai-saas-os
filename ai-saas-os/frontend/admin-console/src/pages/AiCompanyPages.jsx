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

function textPreview(value) {
  if (!value) return '-';
  if (typeof value === 'string') return value;
  return JSON.stringify(value);
}

function SearchableAiCompanyTable({
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

export function AiCompanyDashboardPage() {
  const { data, loading } = useApiData('/admin/ai-company/dashboard', {});
  const cards = [
    ['任务草案', data.tasks_count],
    ['需求想法', data.ideas_count],
    ['路线图', data.roadmaps_count],
    ['版本计划', data.release_plans_count],
    ['质量报告', data.quality_reports_count],
    ['风险报告', data.risk_reports_count],
    ['Codex 指令', data.codex_prompts_count],
    ['待审批', data.pending_approval_count],
  ];

  return (
    <Spin spinning={loading}>
      <Typography.Title level={3}>AI Company OS</Typography.Title>
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
          <Card title="最近任务">
            <Table
              rowKey="id"
              size="small"
              pagination={false}
              locale={{ emptyText: '暂无 AI Company 任务' }}
              dataSource={data.recent_tasks || []}
              columns={[
                { title: '任务', dataIndex: 'title', ellipsis: true },
                { title: '优先级', dataIndex: 'priority' },
                { title: '状态', render: (_, row) => statusTag(row.status) },
                { title: '模式', render: (_, row) => simulationTag(row.simulation_mode) },
              ]}
            />
          </Card>
        </Col>
        <Col xs={24} lg={12}>
          <Card title="最近 Codex 指令">
            <Table
              rowKey="id"
              size="small"
              pagination={false}
              locale={{ emptyText: '暂无 Codex 指令草案' }}
              dataSource={data.recent_prompts || []}
              columns={[
                { title: '标题', dataIndex: 'title', ellipsis: true },
                { title: '目标版本', dataIndex: 'target_version' },
                { title: '审批', render: (_, row) => approvalTag(row.requires_approval) },
                { title: '生成时间', render: (_, row) => dateTime(row.generated_at) },
              ]}
            />
          </Card>
        </Col>
      </Row>
    </Spin>
  );
}

export function AiCompanyTasksPage() {
  return (
    <SearchableAiCompanyTable
      title="AI 任务池"
      endpoint="/admin/ai-company/tasks"
      placeholder="搜索任务、分类、优先级或建议"
      emptyText="暂无 AI 任务草案"
      searchText={(row) => `${row.title || ''} ${row.category || ''} ${row.priority || ''} ${row.recommendation || ''}`}
      columns={[
        { title: '任务', dataIndex: 'title', ellipsis: true },
        { title: '分类', dataIndex: 'category' },
        { title: '优先级', dataIndex: 'priority' },
        { title: '状态', render: (_, row) => statusTag(row.status) },
        { title: '审批', render: (_, row) => approvalTag(row.requires_approval) },
        { title: '模式', render: (_, row) => simulationTag(row.simulation_mode) },
        { title: '来源', dataIndex: 'source' },
        { title: '创建时间', render: (_, row) => dateTime(row.created_at) },
      ]}
    />
  );
}

export function AiCompanyIdeasPage() {
  return (
    <SearchableAiCompanyTable
      title="AI 需求池"
      endpoint="/admin/ai-company/ideas"
      placeholder="搜索想法、描述或来源"
      emptyText="暂无 AI 需求草案"
      searchText={(row) => `${row.title || ''} ${row.description || ''} ${row.source || ''}`}
      columns={[
        { title: '标题', dataIndex: 'title', ellipsis: true },
        { title: '评分', dataIndex: 'score' },
        { title: '状态', render: (_, row) => statusTag(row.status) },
        { title: '模式', render: (_, row) => simulationTag(row.simulation_mode) },
        { title: '来源', dataIndex: 'source' },
        { title: '创建时间', render: (_, row) => dateTime(row.created_at) },
      ]}
    />
  );
}

export function AiCompanyRoadmapPage() {
  return (
    <SearchableAiCompanyTable
      title="AI 产品路线图"
      endpoint="/admin/ai-company/roadmaps"
      placeholder="搜索路线图、版本或摘要"
      emptyText="暂无 AI 路线图草案"
      searchText={(row) => `${row.title || ''} ${row.version || ''} ${row.summary || ''}`}
      columns={[
        { title: '标题', dataIndex: 'title', ellipsis: true },
        { title: '版本', dataIndex: 'version' },
        { title: '条目数', render: (_, row) => countItems(row.items) },
        { title: '状态', render: (_, row) => statusTag(row.status) },
        { title: '模式', render: (_, row) => simulationTag(row.simulation_mode) },
        { title: '创建时间', render: (_, row) => dateTime(row.created_at) },
      ]}
    />
  );
}

export function AiCompanyReleasesPage() {
  return (
    <SearchableAiCompanyTable
      title="AI 版本计划"
      endpoint="/admin/ai-company/release-plans"
      placeholder="搜索版本、标题或部署说明"
      emptyText="暂无 AI 版本计划"
      searchText={(row) => `${row.version || ''} ${row.title || ''} ${row.deployment_notes || ''}`}
      columns={[
        { title: '版本', dataIndex: 'version' },
        { title: '标题', dataIndex: 'title', ellipsis: true },
        { title: '范围', render: (_, row) => countItems(row.scope) },
        { title: '质量门禁', render: (_, row) => countItems(row.quality_gate) },
        { title: '审批', render: (_, row) => approvalTag(row.requires_approval) },
        { title: '状态', render: (_, row) => statusTag(row.status) },
      ]}
    />
  );
}

export function AiCompanyQualityPage() {
  return (
    <SearchableAiCompanyTable
      title="AI 质量评分"
      endpoint="/admin/ai-company/quality-reports"
      placeholder="搜索版本、状态或建议"
      emptyText="暂无 AI 质量报告"
      searchText={(row) => `${row.version || ''} ${row.status || ''} ${textPreview(row.recommendations)}`}
      columns={[
        { title: '版本', dataIndex: 'version' },
        { title: '评分', dataIndex: 'score' },
        { title: '检查项', render: (_, row) => countItems(row.checks) },
        { title: '缺口', render: (_, row) => countItems(row.gaps) },
        { title: '状态', render: (_, row) => statusTag(row.status) },
        { title: '生成时间', render: (_, row) => dateTime(row.generated_at) },
      ]}
    />
  );
}

export function AiCompanyRisksPage() {
  return (
    <SearchableAiCompanyTable
      title="AI 风险清单"
      endpoint="/admin/ai-company/risk-reports"
      placeholder="搜索版本、风险或缓解措施"
      emptyText="暂无 AI 风险报告"
      searchText={(row) => `${row.version || ''} ${row.severity || ''} ${textPreview(row.risks)} ${textPreview(row.mitigations)}`}
      columns={[
        { title: '版本', dataIndex: 'version' },
        { title: '严重级别', dataIndex: 'severity' },
        { title: '风险数', render: (_, row) => countItems(row.risks) },
        { title: '缓解项', render: (_, row) => countItems(row.mitigations) },
        { title: '状态', render: (_, row) => statusTag(row.status) },
        { title: '生成时间', render: (_, row) => dateTime(row.generated_at) },
      ]}
    />
  );
}

export function AiCompanyPromptsPage() {
  return (
    <SearchableAiCompanyTable
      title="AI Codex 指令生成器"
      endpoint="/admin/ai-company/codex-prompts"
      placeholder="搜索标题、目标版本或指令内容"
      emptyText="暂无 Codex 指令草案"
      searchText={(row) => `${row.title || ''} ${row.target_version || ''} ${row.prompt || ''}`}
      columns={[
        { title: '标题', dataIndex: 'title', ellipsis: true },
        { title: '目标版本', dataIndex: 'target_version' },
        { title: '指令摘要', dataIndex: 'prompt', ellipsis: true },
        { title: '审批', render: (_, row) => approvalTag(row.requires_approval) },
        { title: '状态', render: (_, row) => statusTag(row.status) },
        { title: '生成时间', render: (_, row) => dateTime(row.generated_at) },
      ]}
    />
  );
}

export function AiCompanyReportsPage() {
  return (
    <SearchableAiCompanyTable
      title="AI 每日运营报告"
      endpoint="/admin/ai-company/daily-reports"
      placeholder="搜索日期、摘要或下一步"
      emptyText="暂无 AI 每日运营报告"
      searchText={(row) => `${row.report_date || ''} ${row.summary || ''} ${textPreview(row.next_steps)}`}
      columns={[
        { title: '日期', dataIndex: 'report_date' },
        { title: '摘要', dataIndex: 'summary', ellipsis: true },
        { title: '产品项', render: (_, row) => countItems(row.product) },
        { title: '技术项', render: (_, row) => countItems(row.technology) },
        { title: '销售项', render: (_, row) => countItems(row.sales) },
        { title: '状态', render: (_, row) => statusTag(row.status) },
        { title: '生成时间', render: (_, row) => dateTime(row.generated_at) },
      ]}
    />
  );
}
