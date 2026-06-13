export function tablePagination(pageSize = 10) {
  return {
    defaultPageSize: pageSize,
    showSizeChanger: true,
    pageSizeOptions: ['10', '20', '50', '100'],
    showTotal: (total) => `共 ${total} 条`,
  };
}

export function filterRows(rows, keyword, toSearchText) {
  const normalized = String(keyword || '').trim().toLowerCase();
  if (!normalized) return rows;

  return rows.filter((row) => String(toSearchText(row) || '').toLowerCase().includes(normalized));
}
