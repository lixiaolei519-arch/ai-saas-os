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

function OperationsDraftTable({ title, endpoint, placeholder, emptyText }) {
  const [keyword, setKeyword] = useState('');
  const { data, loading } = useApiData(`${endpoint}?limit=100`, []);
  const rows = useMemo(
    () => filterRows(data, keyword, (row) => `${row.type || ''} ${row.title || ''} ${row.content || ''} ${row.channel || ''} ${row.target_audience || ''}`),
    [data, keyword],
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
        columns={[
          { title: '类型', dataIndex: 'type' },
          { title: '标题', dataIndex: 'title', ellipsis: true },
          { title: '内容摘要', dataIndex: 'content', ellipsis: true },
          { title: '渠道', dataIndex: 'channel' },
          { title: '受众', dataIndex: 'target_audience' },
          { title: '审批', render: (_, row) => approvalTag(row.requires_approval) },
          { title: '模式', render: (_, row) => simulationTag(row.simulation_mode) },
          { title: '状态', render: (_, row) => statusTag(row.status) },
          { title: '生成时间', render: (_, row) => dateTime(row.generated_at) },
        ]}
      />
    </>
  );
}

export function OperationsDashboardPage() {
  const { data, loading } = useApiData('/admin/operations/dashboard', {});
  const cards = [
    ['运营草案', data.drafts_count],
    ['运营任务', data.tasks_count],
    ['待审批', data.pending_approval_count],
    ['报告草案', data.reports_count],
    ['SEO 计划', data.seo_plans_count],
    ['落地页草案', data.landing_pages_count],
    ['客户邮件', data.customer_emails_count],
  ];

  return (
    <Spin spinning={loading}>
      <Typography.Title level={3}>无人运营中心</Typography.Title>
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
          <Card title="最近草案">
            <Table
              rowKey="id"
              size="small"
              pagination={false}
              locale={{ emptyText: '暂无运营草案' }}
              dataSource={data.recent_drafts || []}
              columns={[
                { title: '标题', dataIndex: 'title', ellipsis: true },
                { title: '类型', dataIndex: 'type' },
                { title: '审批', render: (_, row) => approvalTag(row.requires_approval) },
              ]}
            />
          </Card>
        </Col>
        <Col xs={24} lg={12}>
          <Card title="最近任务">
            <Table
              rowKey="id"
              size="small"
              pagination={false}
              locale={{ emptyText: '暂无运营任务' }}
              dataSource={data.recent_tasks || []}
              columns={[
                { title: '标题', dataIndex: 'title', ellipsis: true },
                { title: '类型', dataIndex: 'type' },
                { title: '优先级', dataIndex: 'priority' },
                { title: '状态', render: (_, row) => statusTag(row.status) },
              ]}
            />
          </Card>
        </Col>
      </Row>
    </Spin>
  );
}

export function OperationsReportsPage() {
  return <OperationsDraftTable title="运营报告" endpoint="/admin/operations/reports" placeholder="搜索日报、周报或内容" emptyText="暂无运营报告草案" />;
}

export function OperationsSeoPlansPage() {
  return <OperationsDraftTable title="SEO 内容计划" endpoint="/admin/operations/seo-plans" placeholder="搜索 SEO 计划" emptyText="暂无 SEO 内容计划" />;
}

export function OperationsLandingPagesPage() {
  return <OperationsDraftTable title="落地页文案草案" endpoint="/admin/operations/landing-pages" placeholder="搜索落地页文案" emptyText="暂无落地页草案" />;
}

export function OperationsPricingPage() {
  return <OperationsDraftTable title="价格策略建议" endpoint="/admin/operations/pricing" placeholder="搜索价格策略" emptyText="暂无价格策略建议" />;
}

export function OperationsReleaseAnnouncementsPage() {
  return <OperationsDraftTable title="版本发布公告" endpoint="/admin/operations/release-announcements" placeholder="搜索发布公告" emptyText="暂无发布公告草案" />;
}

export function OperationsCustomerEmailsPage() {
  return <OperationsDraftTable title="客户邮件草案" endpoint="/admin/operations/customer-emails" placeholder="搜索客户邮件" emptyText="暂无客户邮件草案" />;
}

export function OperationsFaqPage() {
  return <OperationsDraftTable title="售后 FAQ 草案" endpoint="/admin/operations/faq" placeholder="搜索 FAQ" emptyText="暂无售后 FAQ 草案" />;
}

export function OperationsPartnerRecruitingPage() {
  return <OperationsDraftTable title="代理招募文案" endpoint="/admin/operations/partner-recruiting" placeholder="搜索代理招募文案" emptyText="暂无代理招募文案" />;
}
