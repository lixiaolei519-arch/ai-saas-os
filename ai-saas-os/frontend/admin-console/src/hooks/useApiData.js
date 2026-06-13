import { useEffect, useState } from 'react';
import { api, errorMessage } from '../api/client.js';

export function useApiData(path, fallback = []) {
  const [data, setData] = useState(fallback);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    let active = true;
    setLoading(true);
    api.get(path)
      .then((response) => {
        if (active) setData(response.data.data ?? fallback);
      })
      .catch((error) => errorMessage(error))
      .finally(() => {
        if (active) setLoading(false);
      });

    return () => {
      active = false;
    };
  }, [path]);

  return { data, loading, setData };
}
