import express from "express";
import session from "express-session";
import cors from "cors";
import bcrypt from "bcrypt";
import pool from "./db.js";

const app = express();
const PORT = process.env.PORT || 4000;

app.use(cors({
  origin: process.env.CLIENT_ORIGIN || "http://localhost:5173",
  credentials: true
}));
app.use(express.json());
app.use(session({
  secret: process.env.SESSION_SECRET || "schoolx-secret",
  resave: false,
  saveUninitialized: false,
  cookie: { httpOnly: true, sameSite: "lax" }
}));

const requireAuth = (req, res, next) => {
  if (!req.session.user) {
    return res.status(401).json({ message: "Unauthorized" });
  }
  return next();
};

app.get("/api/health", (_req, res) => {
  res.json({ status: "ok" });
});

app.post("/api/login", async (req, res) => {
  const { username, password } = req.body;
  if (!username || !password) {
    return res.status(400).json({ message: "Missing credentials" });
  }

  try {
    const [rows] = await pool.execute(
      "SELECT * FROM users WHERE username = ? LIMIT 1",
      [username.trim()]
    );
    const user = rows[0];
    if (!user) {
      return res.status(401).json({ message: "Invalid credentials" });
    }

    const storedHash = user.password?.replace(/^\$2y\$/, "$2b$");
    const valid = storedHash ? await bcrypt.compare(password.trim(), storedHash) : false;
    if (!valid) {
      return res.status(401).json({ message: "Invalid credentials" });
    }

    req.session.user = {
      username: user.username,
      role: user.role,
      stage: user.stage,
      canAdd: Boolean(user.can_add),
      canEdit: Boolean(user.can_edit),
      canDelete: Boolean(user.can_delete),
      canAdminData: Boolean(user.can_admin_data),
      isStageAdmin: Boolean(user.is_stage_admin),
      isGeneralManager: Boolean(user.is_general_manager)
    };

    return res.json({ user: req.session.user });
  } catch (error) {
    return res.status(500).json({ message: "Server error" });
  }
});

app.post("/api/logout", requireAuth, (req, res) => {
  req.session.destroy(() => {
    res.json({ message: "Logged out" });
  });
});

app.get("/api/session", (req, res) => {
  res.json({ user: req.session.user || null });
});

app.get("/api/dashboard/stage", requireAuth, async (req, res) => {
  const stage = req.session.user.stage;
  if (!stage) {
    return res.status(400).json({ message: "Stage missing" });
  }

  const tables = [
    { key: "teachers", table: "personal_data" },
    { key: "admins", table: "administration_personal_data" },
    { key: "supervisors", table: "supervisors_data" },
    { key: "workers", table: "workers_data" }
  ];

  try {
    const results = {};
    for (const item of tables) {
      const [rows] = await pool.execute(
        `SELECT COUNT(*) as total FROM ${item.table} WHERE stage = ?`,
        [stage]
      );
      results[item.key] = rows[0]?.total ?? 0;
    }

    res.json({ stage, counts: results });
  } catch (error) {
    res.status(500).json({ message: "Server error" });
  }
});

app.get("/api/dashboard/manager", requireAuth, async (req, res) => {
  if (req.session.user.role !== "general_manager") {
    return res.status(403).json({ message: "Forbidden" });
  }

  const stages = ["ابتدائي", "اعدادي", "ثانوي"];
  const tables = {
    workers: "workers_data",
    teachers: "personal_data",
    admins: "administration_personal_data",
    supervisors: "supervisors_data"
  };

  try {
    const data = {};
    for (const stage of stages) {
      data[stage] = {};
      for (const [key, table] of Object.entries(tables)) {
        const [rows] = await pool.execute(
          `SELECT COUNT(*) as total FROM ${table} WHERE stage = ?`,
          [stage]
        );
        data[stage][key] = rows[0]?.total ?? 0;
      }
    }
    res.json({ data });
  } catch (error) {
    res.status(500).json({ message: "Server error" });
  }
});

app.get("/api/teachers", requireAuth, async (req, res) => {
  const stage = req.session.user.stage;
  if (!stage) {
    return res.status(400).json({ message: "Stage missing" });
  }

  const search = `%${(req.query.search || "").trim()}%`;

  try {
    const [rows] = await pool.execute(
      `SELECT p.employee_id, p.full_name, p.job_title, p.department, a.image
       FROM personal_data p
       LEFT JOIN admin_data a ON p.employee_id = a.employee_id
       WHERE (p.full_name LIKE ? OR p.employee_id LIKE ?) AND TRIM(p.stage) = ?
       ORDER BY p.full_name ASC`,
      [search, search, stage]
    );
    res.json({ teachers: rows });
  } catch (error) {
    res.status(500).json({ message: "Server error" });
  }
});

app.listen(PORT, () => {
  console.log(`Server running on port ${PORT}`);
});
