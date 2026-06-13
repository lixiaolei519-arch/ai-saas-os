import { Card, Col, Row, Statistic, Spin, Table, Typography } from 'antd';
import { dateTime, statusTag, yuan } from '../utils/format.jsx';
import { useApiData } from '../hooks/useApiData.js';

export default function DashboardPage() {
  const { data: stats, loading } = useApiData('/admin/dashboard', {});
  const cards = [
    ['用户数', stats.users_count],
    ['租户数', stats.tenants_count],
    ['License 数', stats.licenses_count],
    ['订单数', stats.orders_count],
    ['已支付订单数', stats.paid_orders_count],
    ['待支付订单数', stats.pending_orders_count],
    ['今日收入', yuan(stats.today_revenue_cents)],
    ['本月收入', yuan(stats.month_revenue_cents)],
    ['佣金总额', yuan(stats.commission_amount_cents)],
    ['今日订单', stats.today_orders_count],
    ['今日新增用户', stats.today_users_count],
  ];

  return (
    <Spin spinning={loading}>
      <Typography.Title level={3}>后台首页</Typography.Title>
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
          <Card title="订单趋势">
            <Table
              rowKey="date"
              size="small"
              pagination={false}
              locale={{ emptyText: '暂无订单趋势' }}
              dataSource={stats.order_trend || []}
              columns={[
                { title: '日期', dataIndex: 'date' },
                { title: '订单数', dataIndex: 'orders_count' },
              ]}
            />
          </Card>
        </Col>
        <Col xs={24} lg={12}>
          <Card title="收入趋势">
            <Table
              rowKey="date"
              size="small"
              pagination={false}
              locale={{ emptyText: '暂无收入趋势' }}
              dataSource={stats.revenue_trend || []}
              columns={[
                { title: '日期', dataIndex: 'date' },
                { title: '收入', render: (_, row) => yuan(row.revenue_cents) },
              ]}
            />
          </Card>
        </Col>
      </Row>
      <Row gutter={[16, 16]} className="dashboard-section">
        <Col xs={24} lg={12}>
          <Card title="License 状态分布">
            <Table
              rowKey="status"
              size="small"
              pagination={false}
              locale={{ emptyText: '暂无 License 状态' }}
              dataSource={stats.license_status_distribution || []}
              columns={[
                { title: '状态', render: (_, row) => statusTag(row.status) },
                { title: '数量', dataIndex: 'count' },
              ]}
            />
          </Card>
        </Col>
        <Col xs={24} lg={12}>
          <Card title="佣金状态分布">
            <Table
              rowKey="status"
              size="small"
              pagination={false}
              locale={{ emptyText: '暂无佣金状态' }}
              dataSource={stats.commission_status_distribution || []}
              columns={[
                { title: '状态', render: (_, row) => statusTag(row.status) },
                { title: '数量', dataIndex: 'count' },
              ]}
            />
          </Card>
        </Col>
      </Row>
      <Row gutter={[16, 16]} className="dashboard-section">
        <Col xs={24} xl={8}>
          <Card title="最近订单">
            <Table
              rowKey="id"
              size="small"
              pagination={false}
              locale={{ emptyText: '暂无订单' }}
              dataSource={stats.recent_orders || []}
              columns={[
                { title: '订单号', dataIndex: 'order_no', ellipsis: true },
                { title: '金额', render: (_, row) => yuan(row.total_cents) },
                { title: '状态', render: (_, row) => statusTag(row.status) },
              ]}
            />
          </Card>
        </Col>
        <Col xs={24} xl={8}>
          <Card title="最近支付回调">
            <Table
              rowKey="id"
              size="small"
              pagination={false}
              locale={{ emptyText: '暂无支付回调' }}
              dataSource={stats.recent_payment_callbacks || []}
              columns={[
                { title: '渠道', dataIndex: 'channel' },
                { title: '状态', render: (_, row) => statusTag(row.status) },
                { title: '时间', render: (_, row) => dateTime(row.created_at) },
              ]}
            />
          </Card>
        </Col>
        <Col xs={24} xl={8}>
          <Card title="最近 License">
            <Table
              rowKey="id"
              size="small"
              pagination={false}
              locale={{ emptyText: '暂无 License' }}
              dataSource={stats.recent_licenses || []}
              columns={[
                { title: '产品', render: (_, row) => row.product_plan?.name || '-' },
                { title: '状态', render: (_, row) => statusTag(row.status) },
                { title: '到期', render: (_, row) => dateTime(row.expires_at) },
              ]}
            />
          </Card>
        </Col>
      </Row>
    </Spin>
  );
}
