import { useEffect, useState } from 'react';
import { buildBackendAssetUrl } from '../../services/api';

export default function LazyImage({ src, alt = '', className = '', style = {} }) {
  const [loaded, setLoaded] = useState(false);
  const url = src ? (src.startsWith('http') ? src : buildBackendAssetUrl(src)) : '';

  useEffect(() => {
    setLoaded(false);
  }, [url]);

  return (
    <div className={`lazy-placeholder ${loaded ? 'loaded' : ''}`} style={{ display: 'inline-block', ...style }}>
      <img
        src={url}
        alt={alt}
        loading="lazy"
        className={className}
        onLoad={() => setLoaded(true)}
        style={{ display: loaded ? 'block' : 'none', width: '100%', height: 'auto' }}
      />
      {!loaded && <div className="skeleton" style={{ width: '100%', height: style.height || 180 }} />}
    </div>
  );
}
