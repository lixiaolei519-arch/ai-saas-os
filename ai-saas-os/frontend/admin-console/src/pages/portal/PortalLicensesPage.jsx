import { CopyOutlined } from '@ant-design/icons';
import { Button, Input, message, Space, Tag, Typography } from 'antd';
import { ProTable } from '@ant-design/pro-components';
import dayjs from 'dayjs';
import { useMemo, useState } from 'react';
import { dateTime } from '../../utils/format.jsx';
import { filterRows, tablePagination } from '../../utils/table.js';
import { useApiData } from '../../hooks/useApiData.js';

function licenseStatus(row) {
  if (row.status !== 'active') return <Tag color="red">禁用</Tag>;
  if (row.expires_at && dayjs(row.expires_at).isBefore(dayjs())) return <Tag color="orange">过期</Tag>;
  return <Tag color="green">有效</Tag>;
}

async function copyText(text) {
  await navigator.clipboard.writeText(text || '');
  message.success('已复制');
}

export default function PortalLicensesPage() {
  const [keyword, setKeyword] = useState('');
  const { data, loading } = useApiData('/portal/licenses?per_page=100', []);
  const licenses = useMemo(() => filterRows(data, keyword, (row) => `${row.license_key || ''} ${row.domain || ''} ${row.source_order_id || ''} ${row.status || ''}`), [data, keyword]);

  return (
    <>
      <Typography.Title level={3}>我的授权</Typography.Title>
      <Input.Search className="table-search" placeholder="搜索 LicenseKey、域名或订单" allowClear onSearch={setKeyword} onChange={(event) => setKeyword(event.target.value)} />
      <ProTable
        rowKey="id"
        loading={loading}
        search={false}
        options={false}
        dataSource={licenses}
        locale={{ emptyText: '暂无授权' }}
        pagination={tablePagination()}
        columns={[
          { title: 'LicenseKey', dataIndex: 'license_key', copyable: true, ellipsis: true },
          { title: '状态', render: (_, row) => licenseStatus(row) },
          { title: '到期时间', render: (_, row) => dateTime(row.expires_at) },
          { title: '绑定域名', dataIndex: 'domain' },
          { title: '所属订单', render: (_, row) => row.source_order_id || '-' },
          {
            title: '操作',
            render: (_, row) => (
              <Space>
                <Button icon={<CopyOutlined />} onClick={() => copyText(row.license_key)}>复制 LicenseKey</Button>
              </Space>
            ),
          },
        ]}
      />
    </>
  );
}
