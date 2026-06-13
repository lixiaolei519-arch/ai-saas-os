import { Card, Col, Row, Spin, Statistic, Table, Typography } from 'antd';
import { dateTime, statusTag, yuan } from '../../utils/format.jsx';
import { useApiData } from '../../hooks/useApiData.js';

export default function PortalDashboardPage() {
  const { data, loading } = useApiData('/portal/dashboard', {});
  const cards = [
    ['我的 License 数量', data.licenses_count],
    ['我的订单数量', data.orders_count],
    ['我的佣金总额', yuan(data.commission_amount_cents)],
    ['我的推广链接数量', data.promotion_links_count],
    ['AI 余额', `CNY ${Number(data.ai_balance_amount || 0).toLocaleString('zh-CN', { minimumFractionDigits: 6, maximumFractionDigits: 6 })}`],
    ['Token 余额', data.ai_balance_tokens],
  ];

  return (
    <Spin spinning={loading}>
      <Typography.Title level={3}>客户首页</Typography.Title>
      <Row gutter={[16, 16]}>
        {cards.map(([title, value]) => (
          <Col xs={24} sm={12} lg={6} key={title}>
            <Card><Statistic title={title} value={value ?? 0} /></Card>
          </Col>
        ))}
      </Row>
      <Row gutter={[16, 16]} className="portal-dashboard-tables">
        <Col xs={24} lg={12}>
          <Card title="最近订单">
            <Table
              rowKey="id"
              size="small"
              pagination={false}
              locale={{ emptyText: '暂无订单' }}
              dataSource={data.recent_orders || []}
              columns={[
                { title: '订单号', dataIndex: 'order_no' },
                { title: '金额', render: (_, row) => yuan(row.total_cents) },
                { title: '状态', render: (_, row) => statusTag(row.status) },
              ]}
            />
          </Card>
        </Col>
        <Col xs={24} lg={12}>
          <Card title="最近授权">
            <Table
              rowKey="id"
              size="small"
              pagination={false}
              locale={{ emptyText: '暂无授权' }}
              dataSource={data.recent_licenses || []}
              columns={[
                { title: '产品', render: (_, row) => row.product_plan?.name || '-' },
                { title: '域名', dataIndex: 'domain' },
                { title: '到期时间', render: (_, row) => dateTime(row.expires_at) },
              ]}
            />
          </Card>
        </Col>
      </Row>
    </Spin>
  );
}
