import { Routes, Route, Navigate } from "react-router-dom";
import LoginPage from "./pages/LoginPage.jsx";
import ManagerDashboard from "./pages/ManagerDashboard.jsx";
import StageDashboard from "./pages/StageDashboard.jsx";
import TeachersPage from "./pages/TeachersPage.jsx";
import { useEffect, useState } from "react";
import { apiRequest } from "./api.js";

export default function App() {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    let active = true;
    apiRequest("/api/session")
      .then((data) => {
        if (active) {
          setUser(data.user);
        }
      })
      .catch(() => {
        if (active) {
          setUser(null);
        }
      })
      .finally(() => {
        if (active) {
          setLoading(false);
        }
      });

    return () => {
      active = false;
    };
  }, []);

  if (loading) {
    return (
      <div className="loading-screen">
        <div className="spinner" />
        <p>جاري التحميل...</p>
      </div>
    );
  }

  return (
    <Routes>
      <Route path="/" element={user ? <Navigate to="/dashboard" /> : <LoginPage onLogin={setUser} />} />
      <Route
        path="/dashboard"
        element={
          user ? (
            user.role === "general_manager" ? (
              <ManagerDashboard user={user} onLogout={setUser} />
            ) : (
              <StageDashboard user={user} onLogout={setUser} />
            )
          ) : (
            <Navigate to="/" />
          )
        }
      />
      <Route
        path="/teachers"
        element={user ? <TeachersPage user={user} onLogout={setUser} /> : <Navigate to="/" />}
      />
      <Route path="*" element={<Navigate to="/" />} />
    </Routes>
  );
}
