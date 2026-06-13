import { ProTable } from '@ant-design/pro-components';
import { Typography } from 'antd';
import { dateTime, statusTag } from '../utils/format.jsx';
import { useApiData } from '../hooks/useApiData.js';

export default function PaymentsPage() {
  const { data, loading } = useApiData('/admin/payment-callbacks?limit=100', []);

  return (
    <>
      <Typography.Title level={3}>支付回调</Typography.Title>
      <ProTable
        rowKey="id"
        loading={loading}
        search={false}
        options={false}
        dataSource={data}
        locale={{ emptyText: '暂无支付回调' }}
        pagination={{ pageSize: 10, showSizeChanger: true }}
        columns={[
          { title: '订单号', render: (_, row) => row.payment?.order?.order_no || row.out_trade_no || '-' },
          { title: '回调状态', dataIndex: 'status', render: (_, row) => statusTag(row.status) },
          { title: '支付渠道', dataIndex: 'channel' },
          { title: '签名', dataIndex: 'signature_valid', render: (_, row) => row.signature_valid ? statusTag('processed') : statusTag('failed') },
          { title: '创建时间', dataIndex: 'created_at', render: (_, row) => dateTime(row.created_at) },
        ]}
      />
    </>
  );
}
