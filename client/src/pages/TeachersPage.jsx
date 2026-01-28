import { useEffect, useState } from "react";
import { Link } from "react-router-dom";
import { apiRequest } from "../api.js";

export default function TeachersPage({ user, onLogout }) {
  const [teachers, setTeachers] = useState([]);
  const [search, setSearch] = useState("");
  const [error, setError] = useState("");

  const loadTeachers = () => {
    apiRequest(`/api/teachers?search=${encodeURIComponent(search)}`)
      .then((result) => setTeachers(result.teachers || []))
      .catch((err) => setError(err.message || "تعذر تحميل البيانات"));
  };

  useEffect(() => {
    loadTeachers();
  }, []);

  const handleSubmit = (event) => {
    event.preventDefault();
    loadTeachers();
  };

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
            <h1>قائمة المعلمين</h1>
            <span className="tag">مرحلة {user.stage}</span>
          </div>
        </div>
        <div className="header-actions">
          <Link to="/dashboard" className="secondary-btn">
            العودة للوحة التحكم
          </Link>
          <button className="primary-btn" onClick={handleLogout}>
            تسجيل الخروج
          </button>
        </div>
      </header>

      <div className="panel">
        <form className="search-bar" onSubmit={handleSubmit}>
          <input
            type="text"
            placeholder="بحث بالاسم أو رقم الهوية"
            value={search}
            onChange={(event) => setSearch(event.target.value)}
          />
          <button type="submit" className="secondary-btn">
            بحث
          </button>
        </form>
        {error && <div className="notice">{error}</div>}
        <table className="table">
          <thead>
            <tr>
              <th>الاسم</th>
              <th>المسمى الوظيفي</th>
              <th>القسم</th>
              <th>الرقم الوظيفي</th>
            </tr>
          </thead>
          <tbody>
            {teachers.length === 0 ? (
              <tr>
                <td colSpan="4" style={{ textAlign: "center" }}>
                  لا توجد بيانات حالياً
                </td>
              </tr>
            ) : (
              teachers.map((teacher) => (
                <tr key={teacher.employee_id}>
                  <td>{teacher.full_name}</td>
                  <td>{teacher.job_title || "-"}</td>
                  <td>{teacher.department || "-"}</td>
                  <td>{teacher.employee_id}</td>
                </tr>
              ))
            )}
          </tbody>
        </table>
      </div>
    </div>
  );
}
