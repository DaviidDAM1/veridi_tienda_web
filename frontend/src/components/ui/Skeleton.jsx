export default function Skeleton({ width = '100%', height = 12, className = '' }) {
  const style = { width, height, display: 'inline-block' };
  return <div className={`skeleton ${className}`} style={style} />;
}
