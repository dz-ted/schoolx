import { useEffect, useState } from "react";
import { apiRequest } from "../api.js";

export default function ManagerDashboard({ user, onLogout }) {
  const [data, setData] = useState({});
  const [error, setError] = useState("");

  useEffect(() => {
    apiRequest("/api/dashboard/manager")
      .then((result) => setData(result.data))
      .catch((err) => setError(err.message || "تعذر تحميل البيانات"));
  }, []);

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
            <h1>لوحة المدير التنفيذي</h1>
            <span className="tag">{user.username}</span>
          </div>
        </div>
        <div className="header-actions">
          <button className="secondary-btn" onClick={handleLogout}>
            تسجيل الخروج
          </button>
        </div>
      </header>

      <div className="panel">
        <h2 className="section-title">ملخص المراحل الدراسية</h2>
        {error && <div className="notice">{error}</div>}
        <div className="grid">
          {Object.entries(data).map(([stage, counts]) => (
            <div key={stage} className="card">
              <h3>{stage}</h3>
              <p>{(counts.teachers || 0) + (counts.admins || 0) + (counts.workers || 0) + (counts.supervisors || 0)}</p>
              <div style={{ marginTop: "12px", fontSize: "14px", color: "#666" }}>
                <div>المعلمين: {counts.teachers}</div>
                <div>الإداريين: {counts.admins}</div>
                <div>العمال: {counts.workers}</div>
                <div>المشرفين: {counts.supervisors}</div>
              </div>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
}
