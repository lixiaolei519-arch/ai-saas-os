import dayjs from 'dayjs';
import { Tag } from 'antd';

export function yuan(cents = 0) {
  return `¥${(Number(cents || 0) / 100).toLocaleString('zh-CN', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })}`;
}

export function dateTime(value) {
  return value ? dayjs(value).format('YYYY-MM-DD HH:mm') : '-';
}

export function statusTag(value) {
  const status = value || 'unknown';
  const map = {
    active: ['green', '正常'],
    paid: ['green', '已支付'],
    processed: ['green', '已处理'],
    pending: ['gold', '待处理'],
    processing: ['blue', '处理中'],
    inactive: ['red', '停用'],
    failed: ['red', '失败'],
    rejected: ['red', '拒绝'],
    draft: ['default', '草稿'],
  };
  const [color, label] = map[status] || ['default', status];
  return <Tag color={color}>{label}</Tag>;
}

export function maskHash(hash) {
  if (!hash) return '-';
  return `****${String(hash).slice(-8)}`;
}
