import { useEffect, useState } from "react";
import { apiRequest } from "../api.js";
import { Link } from "react-router-dom";

export default function StageDashboard({ user, onLogout }) {
  const [counts, setCounts] = useState({});
  const [stage, setStage] = useState(user.stage || "");
  const [error, setError] = useState("");

  useEffect(() => {
    apiRequest("/api/dashboard/stage")
      .then((result) => {
        setCounts(result.counts || {});
        setStage(result.stage || user.stage);
      })
      .catch((err) => setError(err.message || "تعذر تحميل البيانات"));
  }, [user.stage]);

  const handleLogout = async () => {
    await apiRequest("/api/logout", { method: "POST" });
    onLogout(null);
  };

  return (
    <div className="app-shell">
      <header className="shell-header">
        <div className="brand">
          <div className="brand-logo">FX</div>
          <div>
            <h1>لوحة مرحلة {stage}</h1>
            <span className="tag">{user.username}</span>
          </div>
        </div>
        <div className="header-actions">
          <Link to="/teachers" className="secondary-btn">
            قائمة المعلمين
          </Link>
          <button className="primary-btn" onClick={handleLogout}>
            تسجيل الخروج
          </button>
        </div>
      </header>

      <div className="panel">
        <h2 className="section-title">مؤشرات موظفي المرحلة</h2>
        {error && <div className="notice">{error}</div>}
        <div className="grid">
          <div className="card">
            <h3>المعلمين</h3>
            <p>{counts.teachers ?? 0}</p>
          </div>
          <div className="card">
            <h3>الإداريين</h3>
            <p>{counts.admins ?? 0}</p>
          </div>
          <div className="card">
            <h3>المشرفين</h3>
            <p>{counts.supervisors ?? 0}</p>
          </div>
          <div className="card">
            <h3>العمال</h3>
            <p>{counts.workers ?? 0}</p>
          </div>
        </div>
      </div>
    </div>
  );
}
