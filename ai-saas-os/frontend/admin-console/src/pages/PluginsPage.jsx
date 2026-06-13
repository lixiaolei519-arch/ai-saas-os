import { ProTable } from '@ant-design/pro-components';
import { Button, Card, Form, Input, InputNumber, Space, Typography, message } from 'antd';
import { useMemo, useState } from 'react';
import { api, errorMessage } from '../api/client.js';
import { dateTime, statusTag, yuan } from '../utils/format.jsx';
import { filterRows, tablePagination } from '../utils/table.js';
import { useApiData } from '../hooks/useApiData.js';

export default function PluginsPage() {
  const [keyword, setKeyword] = useState('');
  const [creating, setCreating] = useState(false);
  const [uploading, setUploading] = useState(false);
  const [createForm] = Form.useForm();
  const [releaseForm] = Form.useForm();
  const { data, loading, setData } = useApiData('/admin/plugins?limit=100', []);
  const plugins = useMemo(
    () => filterRows(data, keyword, (row) => `${row.name || ''} ${row.slug || ''} ${row.status || ''}`),
    [data, keyword],
  );

  const createPlugin = async (values) => {
    setCreating(true);
    try {
      const response = await api.post('/plugins', values);
      setData((rows) => [response.data.data, ...rows]);
      createForm.resetFields();
      message.success('插件包已创建');
    } catch (error) {
      errorMessage(error);
    } finally {
      setCreating(false);
    }
  };

  const uploadRelease = async (values) => {
    setUploading(true);
    try {
      await api.post(`/plugins/${values.plugin_id}/releases`, values);
      releaseForm.resetFields();
      message.success('插件版本已创建，刷新后可查看最新版本');
    } catch (error) {
      errorMessage(error);
    } finally {
      setUploading(false);
    }
  };

  return (
    <>
      <Typography.Title level={3}>插件交付</Typography.Title>
      <Card title="插件上传" className="dashboard-section">
        <Form form={createForm} layout="inline" onFinish={createPlugin}>
          <Form.Item name="name" rules={[{ required: true, message: '请输入插件名称' }]}>
            <Input placeholder="插件名称" />
          </Form.Item>
          <Form.Item name="slug">
            <Input placeholder="标识" />
          </Form.Item>
          <Form.Item name="version">
            <Input placeholder="版本号" />
          </Form.Item>
          <Form.Item name="package_path" rules={[{ required: true, message: '请输入包路径' }]}>
            <Input placeholder="plugins/example.zip" />
          </Form.Item>
          <Form.Item name="size_bytes">
            <InputNumber min={0} placeholder="大小" />
          </Form.Item>
          <Button type="primary" htmlType="submit" loading={creating}>创建</Button>
        </Form>
      </Card>
      <Card title="版本管理" className="dashboard-section">
        <Form form={releaseForm} layout="inline" onFinish={uploadRelease}>
          <Form.Item name="plugin_id" rules={[{ required: true, message: '请输入插件 ID' }]}>
            <InputNumber min={1} placeholder="插件 ID" />
          </Form.Item>
          <Form.Item name="version" rules={[{ required: true, message: '请输入版本号' }]}>
            <Input placeholder="版本号" />
          </Form.Item>
          <Form.Item name="package_path" rules={[{ required: true, message: '请输入包路径' }]}>
            <Input placeholder="plugins/example-1.1.0.zip" />
          </Form.Item>
          <Form.Item name="size_bytes">
            <InputNumber min={0} placeholder="大小" />
          </Form.Item>
          <Button htmlType="submit" loading={uploading}>新增版本</Button>
        </Form>
      </Card>
      <Input.Search className="table-search" placeholder="搜索插件名称、标识或状态" allowClear onSearch={setKeyword} onChange={(event) => setKeyword(event.target.value)} />
      <ProTable
        rowKey="id"
        loading={loading}
        search={false}
        options={false}
        dataSource={plugins}
        locale={{ emptyText: '暂无插件' }}
        pagination={tablePagination()}
        columns={[
          { title: 'ID', dataIndex: 'id', width: 72 },
          { title: '插件名称', dataIndex: 'name' },
          { title: '标识', dataIndex: 'slug', copyable: true },
          { title: '价格', render: (_, row) => yuan(row.price_cents) },
          { title: '状态', render: (_, row) => statusTag(row.status) },
          { title: '版本', render: (_, row) => <Space wrap>{(row.releases || []).map((release) => `${release.version} ${release.status}`)}</Space> },
          { title: '包路径', render: (_, row) => row.releases?.[0]?.packages?.[0]?.storage_path || '-' },
          { title: '创建时间', render: (_, row) => dateTime(row.created_at) },
        ]}
      />
    </>
  );
}
