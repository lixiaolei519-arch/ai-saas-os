import { ProTable } from '@ant-design/pro-components';
import { Input, Typography } from 'antd';
import { useMemo, useState } from 'react';
import { dateTime, statusTag } from '../utils/format.jsx';
import { filterRows, tablePagination } from '../utils/table.js';
import { useApiData } from '../hooks/useApiData.js';

export default function PluginDownloadsPage() {
  const [keyword, setKeyword] = useState('');
  const { data, loading } = useApiData('/admin/plugin-downloads?limit=100', []);
  const records = useMemo(
    () => filterRows(data, keyword, (row) => `${row.plugin?.name || ''} ${row.release?.version || ''} ${row.tenant?.name || ''} ${row.status || ''}`),
    [data, keyword],
  );

  return (
    <>
      <Typography.Title level={3}>插件下载记录</Typography.Title>
      <Input.Search className="table-search" placeholder="搜索插件、版本、租户或状态" allowClear onSearch={setKeyword} onChange={(event) => setKeyword(event.target.value)} />
      <ProTable
        rowKey="id"
        loading={loading}
        search={false}
        options={false}
        dataSource={records}
        locale={{ emptyText: '暂无下载记录' }}
        pagination={tablePagination()}
        columns={[
          { title: '插件', render: (_, row) => row.plugin?.name || row.plugin_id },
          { title: '租户', render: (_, row) => row.tenant?.name || row.tenant_id },
          { title: '版本', render: (_, row) => row.release?.version || row.plugin_release_id },
          { title: '包文件', render: (_, row) => row.package?.file_name || '-' },
          { title: '状态', render: (_, row) => statusTag(row.status) },
          { title: '下载时间', render: (_, row) => dateTime(row.downloaded_at) },
        ]}
      />
    </>
  );
}
