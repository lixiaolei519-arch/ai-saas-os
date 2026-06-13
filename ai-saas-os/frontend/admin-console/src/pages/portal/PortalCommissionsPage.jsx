import { ProTable } from '@ant-design/pro-components';
import { Typography } from 'antd';
import { dateTime, statusTag, yuan } from '../../utils/format.jsx';
import { useApiData } from '../../hooks/useApiData.js';

export default function PortalCommissionsPage() {
  const { data, loading } = useApiData('/portal/commissions?per_page=100', []);

  return (
    <>
      <Typography.Title level={3}>我的佣金</Typography.Title>
      <ProTable
        rowKey="id"
        loading={loading}
        search={false}
        options={false}
        dataSource={data}
        locale={{ emptyText: '暂无佣金' }}
        pagination={{ pageSize: 10, showSizeChanger: true }}
        columns={[
          { title: '来源订单', dataIndex: 'order_id' },
          { title: '佣金金额', render: (_, row) => yuan(row.commission_amount_cents) },
          { title: '状态', render: (_, row) => statusTag(row.status) },
          { title: '创建时间', render: (_, row) => dateTime(row.created_at) },
        ]}
      />
    </>
  );
}
