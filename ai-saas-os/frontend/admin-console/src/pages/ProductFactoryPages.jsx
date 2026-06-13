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

function TemplateTable({ title, endpoint, placeholder, emptyText }) {
  const [keyword, setKeyword] = useState('');
  const { data, loading } = useApiData(`${endpoint}?limit=100`, []);
  const rows = useMemo(
    () => filterRows(data, keyword, (row) => `${row.type || ''} ${row.name || ''} ${row.description || ''}`),
    [data, keyword],
  );

  return (
    <>
      <Typography.Title level={3}>{title}</Typography.Title>
      <Input.Search className="table-search" placeholder={placeholder} allowClear onSearch={setKeyword} onChange={(event) => setKeyword(event.target.value)} />
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
          { title: '名称', dataIndex: 'name', ellipsis: true },
          { title: '描述', dataIndex: 'description', ellipsis: true },
          { title: '审批', render: (_, row) => approvalTag(row.requires_approval) },
          { title: '模式', render: (_, row) => simulationTag(row.simulation_mode) },
          { title: '状态', render: (_, row) => statusTag(row.status) },
          { title: '创建时间', render: (_, row) => dateTime(row.created_at) },
        ]}
      />
    </>
  );
}

export function ProductFactoryDashboardPage() {
  const { data, loading } = useApiData('/admin/product-factory/dashboard', {});
  const cards = [
    ['模板数', data.templates_count],
    ['草案数', data.drafts_count],
    ['发布清单', data.launch_checklists_count],
    ['待审批', data.pending_approval_count],
  ];

  return (
    <Spin spinning={loading}>
      <Typography.Title level={3}>产品工厂</Typography.Title>
      <Row gutter={[16, 16]}>
        {cards.map(([title, value]) => (
          <Col xs={24} sm={12} lg={6} key={title}>
            <Card><Statistic title={title} value={value ?? 0} /></Card>
          </Col>
        ))}
      </Row>
      <Row gutter={[16, 16]} className="dashboard-section">
        <Col xs={24} lg={12}>
          <Card title="最近模板">
            <Table
              rowKey="id"
              size="small"
              pagination={false}
              locale={{ emptyText: '暂无产品工厂模板' }}
              dataSource={data.recent_templates || []}
              columns={[
                { title: '名称', dataIndex: 'name', ellipsis: true },
                { title: '类型', dataIndex: 'type' },
                { title: '审批', render: (_, row) => approvalTag(row.requires_approval) },
              ]}
            />
          </Card>
        </Col>
        <Col xs={24} lg={12}>
          <Card title="最近草案">
            <Table
              rowKey="id"
              size="small"
              pagination={false}
              locale={{ emptyText: '暂无产品工厂草案' }}
              dataSource={data.recent_drafts || []}
              columns={[
                { title: '标题', dataIndex: 'title', ellipsis: true },
                { title: '类型', dataIndex: 'type' },
                { title: '状态', render: (_, row) => statusTag(row.status) },
              ]}
            />
          </Card>
        </Col>
      </Row>
    </Spin>
  );
}

export function ProductTemplatesPage() {
  return <TemplateTable title="产品模板" endpoint="/admin/product-factory/product-templates" placeholder="搜索产品模板" emptyText="暂无产品模板" />;
}

export function PluginTemplatesPage() {
  return <TemplateTable title="插件模板" endpoint="/admin/product-factory/plugin-templates" placeholder="搜索插件模板" emptyText="暂无插件模板" />;
}

export function LandingPageTemplatesPage() {
  return <TemplateTable title="落地页模板" endpoint="/admin/product-factory/landing-page-templates" placeholder="搜索落地页模板" emptyText="暂无落地页模板" />;
}

export function PackageTemplatesPage() {
  return <TemplateTable title="套餐模板" endpoint="/admin/product-factory/package-templates" placeholder="搜索价格或 License 套餐模板" emptyText="暂无套餐模板" />;
}

export function LaunchChecklistsPage() {
  const [keyword, setKeyword] = useState('');
  const { data, loading } = useApiData('/admin/product-factory/launch-checklists?limit=100', []);
  const rows = useMemo(
    () => filterRows(data, keyword, (row) => `${row.title || ''} ${JSON.stringify(row.items || [])}`),
    [data, keyword],
  );

  return (
    <>
      <Typography.Title level={3}>发布清单</Typography.Title>
      <Input.Search className="table-search" placeholder="搜索发布清单" allowClear onSearch={setKeyword} onChange={(event) => setKeyword(event.target.value)} />
      <ProTable
        rowKey="id"
        loading={loading}
        search={false}
        options={false}
        dataSource={rows}
        locale={{ emptyText: '暂无产品发布清单' }}
        pagination={tablePagination()}
        columns={[
          { title: '标题', dataIndex: 'title', ellipsis: true },
          { title: '清单项', render: (_, row) => (row.items || []).length },
          { title: '审批', render: (_, row) => approvalTag(row.requires_approval) },
          { title: '模式', render: (_, row) => simulationTag(row.simulation_mode) },
          { title: '状态', render: (_, row) => statusTag(row.status) },
          { title: '生成时间', render: (_, row) => dateTime(row.generated_at) },
        ]}
      />
    </>
  );
}
