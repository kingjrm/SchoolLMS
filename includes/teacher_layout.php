<?php
/**
 * Shared Teacher Layout to keep sidebar/topbar consistent
 */

function teacherLayoutStart(string $active, string $title) {
    echo '<!DOCTYPE html><html lang="en"><head>';
    echo '<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<title>' . htmlspecialchars($title) . ' - School LMS</title>';
    echo '<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">';
    echo "<style>
        *{margin:0;padding:0;box-sizing:border-box}
        :root{--bg-primary:#ffffff;--bg-secondary:#f6f7fb;--bg-sidebar:#ffffff;--text-primary:#0f172a;--text-secondary:#475569;--border-color:#e5e7eb;--shadow:0 1px 3px rgba(0,0,0,.08);--shadow-lg:0 10px 25px rgba(0,0,0,.08);--primary-color:#3b82f6;--primary-dark:#2563eb}
        body{font-family:'Poppins',system-ui,Segoe UI,Roboto,Helvetica,Arial,sans-serif;background:var(--bg-secondary);color:var(--text-primary);display:flex;min-height:100vh}
        .sidebar{width:260px;background:var(--bg-sidebar);color:var(--text-primary);padding:1.25rem;position:fixed;height:100vh;overflow-y:auto;display:flex;flex-direction:column;border-right:1px solid var(--border-color)}
        .sidebar-header{margin-bottom:1.5rem;padding-bottom:1rem;border-bottom:1px solid var(--border-color)}
        .sidebar-header h1{font-size:1.05rem;margin-bottom:.25rem;color:var(--text-primary);letter-spacing:.02em;text-transform:uppercase}
        .sidebar-header p{font-size:.75rem;color:#64748b}
        .sidebar-menu{list-style:none;flex:1}
        .sidebar-menu li{margin-bottom:.5rem}
        .sidebar-menu a{display:flex;align-items:center;gap:.75rem;padding:.65rem .85rem;color:#334155;text-decoration:none;border-radius:.6rem;font-size:.95rem;transition:all .2s}
        .sidebar-menu a svg{width:20px;height:20px;stroke:#64748b;fill:none;stroke-width:2;transition:all .2s}
        .sidebar-menu a:hover{background:#f0f4ff;color:#1e293b}
        .sidebar-menu a:hover svg{stroke:#2563eb}
        .sidebar-menu a.active{background:rgba(59,130,246,.12);color:#1d4ed8;outline:1px solid rgba(59,130,246,.15)}
        .sidebar-menu a.active svg{stroke:#1d4ed8}
        .sidebar-footer{border-top:1px solid var(--border-color);padding-top:1rem;margin-top:auto}
        .footer-links{display:grid;gap:.5rem;margin-bottom:.75rem}
        .footer-link{display:flex;align-items:center;gap:.6rem;color:#334155;text-decoration:none;padding:.45rem .5rem;border-radius:.5rem}
        .footer-link:hover{background:#f1f5f9}
        .footer-link svg{width:18px;height:18px;stroke:#64748b}
        .logout-btn{width:100%;padding:.75rem 1rem;background:#3b82f6;color:#fff;border:none;border-radius:.6rem;font-size:.9rem;cursor:pointer;font-family:inherit;transition:all .2s;display:flex;align-items:center;gap:.5rem;justify-content:center;text-decoration:none}
        .logout-btn:hover{background:#2563eb}
        .main-content{margin-left:260px;flex:1;display:flex;flex-direction:column}
        .topbar{background:var(--bg-secondary);border-bottom:1px solid var(--border-color);padding:1.1rem 1.5rem;display:flex;justify-content:space-between;align-items:center;position:sticky;top:0;z-index:5}
        .topbar h2{font-size:1.05rem;font-weight:700}
        .content{padding:1.25rem;flex:1;overflow-y:auto;width:100%}
        .card{background:var(--bg-primary);border:1px solid var(--border-color);border-radius:1rem;padding:1.1rem;box-shadow:var(--shadow);margin-bottom:1rem}
        .stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1rem;margin-bottom:1rem}
        .stat-card{background:#fff;border:1px solid var(--border-color);border-radius:1rem;padding:1rem;box-shadow:var(--shadow)}
        .stat-value{font-size:1.6rem;font-weight:700;color:var(--primary-color);margin-bottom:.25rem}
        .stat-label{color:var(--text-secondary);font-size:.85rem}
        .chip{display:inline-flex;align-items:center;gap:.35rem;padding:.25rem .6rem;border-radius:999px;font-size:.75rem;border:1px solid var(--border-color);color:#334155;background:#f8fafc}
        .badge{display:inline-block;padding:.2rem .55rem;border-radius:999px;font-size:.7rem;font-weight:600;border:1px solid var(--border-color)}
        .badge-low{background:#ecfeff;color:#155e75}
        .badge-medium{background:#eff6ff;color:#1e3a8a}
        .badge-high{background:#fef2f2;color:#991b1b}
        .table{width:100%;border-collapse:collapse;font-size:.875rem}
        .table th,.table td{padding:.75rem;border-bottom:1px solid #f3f4f6;text-align:left}
        .table thead{background:#f9fafb;border-bottom:2px solid var(--border-color)}
    </style>";
    echo '</head><body>';

    echo '<aside class="sidebar">'
        .'<div class="sidebar-header"><h1>School LMS</h1><p>Teacher Portal</p></div>'
        .'<ul class="sidebar-menu">'
            .'<li><a href="dashboard.php"'.($active==='dashboard'?' class="active"':'').'>
                <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                Dashboard</a></li>'
            .'<li><a href="courses.php"'.($active==='courses'?' class="active"':'').'>
                <svg viewBox="0 0 24 24"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path></svg>
                My Courses</a></li>'
            .'<li><a href="students.php"'.($active==='students'?' class="active"':'').'>
                <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                Students</a></li>'
            .'<li><a href="assignments.php"'.($active==='assignments'?' class="active"':'').'>
                <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                Assignments</a></li>'
            .'<li><a href="grades.php"'.($active==='grades'?' class="active"':'').'>
                <svg viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
                Grades</a></li>'
        .'</ul>'
        .'<div class="sidebar-footer">'
            .'<div class="footer-links">'
                .'<a class="footer-link" href="settings.php">'
                    .'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09a1.65 1.65 0 0 0-1-1.51 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09a1.65 1.65 0 0 0 1.51-1 1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9c0 .69.28 1.35.78 1.82.31.3.31.78 0 1.09A2.64 2.64 0 0 0 19.4 15z"/></svg>'
                    .'Settings'
                .'</a>'
            .'</div>'
            .'<a href="../logout.php" class="logout-btn">'
                .'<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="7" y2="12"/></svg>'
                .'Logout'
            .'</a>'
        .'</div>'
    .'</aside>';

    $fullName = isset($_SESSION['first_name']) ? trim(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '')) : '';
    echo '<div class="main-content">'
        .'<div class="topbar"><h2>' . htmlspecialchars($title) . '</h2>'
        .'<div style="display:flex;align-items:center;gap:.75rem;color:#334155;font-size:.9rem">'
            .'<div style="text-align:right">'
                .'<div style="font-weight:700">' . htmlspecialchars($fullName ?: 'Teacher') . '</div>'
                .'<div id="current-datetime" style="color:#64748b"></div>'
            .'</div>'
            .'<div style="width:36px;height:36px;border-radius:50%;background:#e2e8f0;display:flex;align-items:center;justify-content:center;font-weight:700;color:#475569">' . htmlspecialchars($fullName ? strtoupper($fullName[0]) : 'T') . '</div>'
        .'</div></div><div class="content">';
    echo "<script>\n      (function(){\n        function fmt(d){\n          const opts={weekday:'short',year:'numeric',month:'short',day:'numeric',hour:'numeric',minute:'2-digit'};\n          return new Intl.DateTimeFormat(undefined,opts).format(d);\n        }\n        const el=document.getElementById('current-datetime');\n        function tick(){ if(el){ el.textContent = fmt(new Date()); } }\n        tick();\n        setInterval(tick, 30000);\n      })();\n    </script>";
}

function teacherLayoutEnd() {
    echo '</div></div>';
    echo "</body></html>";
}
