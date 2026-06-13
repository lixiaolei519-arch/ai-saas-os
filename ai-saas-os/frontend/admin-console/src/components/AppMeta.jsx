import { CodeOutlined, ClockCircleOutlined, TagOutlined } from '@ant-design/icons';
import { Space, Tooltip, Typography } from 'antd';
import dayjs from 'dayjs';
import { buildMeta } from '../meta/build.js';

function formatBuildTime(value) {
  return value && value !== 'unknown' ? dayjs(value).format('YYYY-MM-DD HH:mm') : 'unknown';
}

export default function AppMeta({ system = null }) {
  const version = system?.stable_version || buildMeta.version;
  const commit = system?.git_commit || buildMeta.gitCommit;
  const buildTime = formatBuildTime(buildMeta.buildTime);

  return (
    <Typography.Text type="secondary" className="app-meta">
      <Space size={8} wrap>
        <Tooltip title="当前稳定版本">
          <span><TagOutlined /> {version}</span>
        </Tooltip>
        <Tooltip title="当前 Git commit">
          <span><CodeOutlined /> {commit}</span>
        </Tooltip>
        <Tooltip title="前端构建时间">
          <span><ClockCircleOutlined /> {buildTime}</span>
        </Tooltip>
      </Space>
    </Typography.Text>
  );
}
