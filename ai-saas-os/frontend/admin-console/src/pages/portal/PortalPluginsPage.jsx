import { ProTable } from '@ant-design/pro-components';
import { Input, Typography } from 'antd';
import { useMemo, useState } from 'react';
import { dateTime, statusTag } from '../../utils/format.jsx';
import { filterRows, tablePagination } from '../../utils/table.js';
import { useApiData } from '../../hooks/useApiData.js';

export default function PortalPluginsPage() {
  const [keyword, setKeyword] = useState('');
  const { data, loading } = useApiData('/portal/plugins?per_page=100', []);
  const plugins = useMemo(
    () => filterRows(data, keyword, (row) => `${row.plugin?.name || ''} ${row.plugin?.slug || ''} ${row.status || ''}`),
    [data, keyword],
  );

  return (
    <>
      <Typography.Title level={3}>我的插件</Typography.Title>
      <Input.Search className="table-search" placeholder="搜索插件名称、标识或状态" allowClear onSearch={setKeyword} onChange={(event) => setKeyword(event.target.value)} />
      <ProTable
        rowKey="id"
        loading={loading}
        search={false}
        options={false}
        dataSource={plugins}
        locale={{ emptyText: '暂无可下载插件' }}
        pagination={tablePagination()}
        columns={[
          { title: '插件名称', render: (_, row) => row.plugin?.name || '-' },
          { title: '标识', render: (_, row) => row.plugin?.slug || '-' },
          { title: '已安装版本', render: (_, row) => row.release?.version || '-' },
          { title: '包文件', render: (_, row) => row.release?.packages?.[0]?.file_name || '-' },
          { title: '状态', render: (_, row) => statusTag(row.status) },
          { title: '安装时间', render: (_, row) => dateTime(row.installed_at) },
        ]}
      />
    </>
  );
}
