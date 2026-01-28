import { useState } from "react";
import { apiRequest } from "../api.js";

export default function LoginPage({ onLogin }) {
  const [username, setUsername] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);

  const handleSubmit = async (event) => {
    event.preventDefault();
    setError("");
    setLoading(true);
    try {
      const data = await apiRequest("/api/login", {
        method: "POST",
        body: JSON.stringify({ username, password })
      });
      onLogin(data.user);
    } catch (err) {
      setError(err.message || "حدث خطأ أثناء تسجيل الدخول");
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="login-wrapper">
      <div className="login-card">
        <h2>مرحباً بك في منصة المدرسة</h2>
        <p>تسجيل الدخول للوصول إلى لوحة التحكم الخاصة بك.</p>
        {error && <div className="notice">{error}</div>}
        <form onSubmit={handleSubmit}>
          <input
            type="text"
            placeholder="اسم المستخدم"
            value={username}
            onChange={(event) => setUsername(event.target.value)}
            required
          />
          <input
            type="password"
            placeholder="كلمة المرور"
            value={password}
            onChange={(event) => setPassword(event.target.value)}
            required
          />
          <button className="primary-btn" type="submit" disabled={loading}>
            {loading ? "جاري الدخول..." : "تسجيل الدخول"}
          </button>
        </form>
      </div>
    </div>
  );
}
