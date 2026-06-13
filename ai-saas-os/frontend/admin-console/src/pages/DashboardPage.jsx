import { Card, Col, Row, Statistic, Spin, Typography } from 'antd';
import { yuan } from '../utils/format.jsx';
import { useApiData } from '../hooks/useApiData.js';

export default function DashboardPage() {
  const { data: stats, loading } = useApiData('/admin/stats', {});
  const cards = [
    ['用户数', stats.users_count],
    ['租户数', stats.tenants_count],
    ['License 数', stats.licenses_count],
    ['订单数', stats.orders_count],
    ['已支付订单数', stats.paid_orders_count],
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
    </Spin>
  );
}
