<?php
/**
 * Shared Admin Layout
 */

function adminLayoutStart(string $active, string $title) {
    echo '<!DOCTYPE html><html lang="en"><head>';
    echo '<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<title>' . htmlspecialchars($title) . ' - School LMS</title>';
    echo '<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">';
    echo "<style>
    *{margin:0;padding:0;box-sizing:border-box}
    :root{--bg-primary:#ffffff;--bg-secondary:#f6f7fb;--bg-sidebar:#01172a;--text-primary:#01172a;--text-secondary:#6b7280;--border-color:#e5e7eb;--shadow:0 1px 3px rgba(0,0,0,.08);--shadow-lg:0 10px 25px rgba(0,0,0,.08);--primary-color:#f97316;--primary-dark:#ea580c}
    body{font-family:'Poppins',system-ui,Segoe UI,Roboto,Helvetica,Arial,sans-serif;background:var(--bg-secondary);color:var(--text-primary);display:flex;min-height:100vh;font-size:.9rem}
    .sidebar{width:240px;background:var(--bg-sidebar);color:#ffffff;padding:1rem;position:fixed;height:100vh;overflow-y:auto;display:flex;flex-direction:column;border-right:3px solid #f97316}
    .sidebar-header{margin-bottom:1rem;padding-bottom:0.75rem;border-bottom:1px solid rgba(255,255,255,0.1)}
    .sidebar-header h1{font-size:0.85rem;margin-bottom:0.15rem;color:#ffffff;letter-spacing:.02em;text-transform:uppercase;font-weight:700}
    .sidebar-header p{font-size:0.65rem;color:rgba(255,255,255,0.7)}
    .sidebar-menu{list-style:none;flex:1}
    .sidebar-menu li{margin-bottom:0.35rem}
    .sidebar-menu a{display:flex;align-items:center;gap:0.6rem;padding:0.5rem 0.65rem;color:rgba(255,255,255,0.8);text-decoration:none;border-radius:0.5rem;font-size:0.8rem;transition:all .2s}
    .sidebar-menu a svg{width:18px;height:18px;stroke:rgba(255,255,255,0.6);fill:none;stroke-width:2;transition:all .2s}
    .sidebar-menu a:hover{background:rgba(249,115,22,0.2);color:#ffffff}
    .sidebar-menu a:hover svg{stroke:#f97316}
    .sidebar-menu a.active{background:#f97316;color:#ffffff;font-weight:500}
    .sidebar-menu a.active svg{stroke:#ffffff}
    .has-children > a{justify-content:space-between}
    .sidebar-submenu{list-style:none;margin-top:0.25rem;margin-left:1.5rem;border-left:1px solid rgba(255,255,255,0.1);padding-left:0.75rem;display:none}
    .sidebar-submenu li{margin-bottom:0.35rem}
    .sidebar-submenu a{font-size:0.75rem;padding:0.45rem 0.6rem;color:rgba(255,255,255,0.7)}
    .sidebar-submenu a:hover{background:rgba(249,115,22,0.2);color:#ffffff}
    .sidebar-submenu a.active{background:#f97316;color:#ffffff}
    .has-children.open > .sidebar-submenu{display:block}
    .sidebar-footer{border-top:1px solid rgba(255,255,255,0.1);padding-top:0.75rem;margin-top:auto}
    .footer-links{display:grid;gap:0.4rem;margin-bottom:0.6rem}
    .footer-link{display:flex;align-items:center;gap:0.5rem;color:rgba(255,255,255,0.7);text-decoration:none;padding:0.35rem 0.5rem;border-radius:0.5rem;font-size:0.75rem;transition:all .2s}
    .footer-link:hover{background:rgba(249,115,22,0.2);color:#ffffff}
    .footer-link svg{width:16px;height:16px;stroke:rgba(255,255,255,0.6)}
    .logout-btn{width:100%;padding:0.6rem 0.8rem;background:#f97316;color:#fff;border:none;border-radius:0.5rem;font-size:0.8rem;cursor:pointer;font-family:inherit;transition:all .2s;display:flex;align-items:center;gap:0.4rem;justify-content:center;text-decoration:none}
    .logout-btn:hover{background:#ea580c}
    .main-content{margin-left:240px;flex:1;display:flex;flex-direction:column}
    .topbar{background:#ffffff;border-bottom:1px solid var(--border-color);padding:0.9rem 1.2rem;display:flex;justify-content:space-between;align-items:center;position:sticky;top:0;z-index:5;box-shadow:var(--shadow)}
    .topbar h2{font-size:0.95rem;font-weight:700;color:var(--text-primary)}
    .content{padding:1rem;flex:1;overflow-y:auto;width:100%}
    .card{background:var(--bg-primary);border:1px solid var(--border-color);border-radius:0.9rem;padding:0.9rem;box-shadow:var(--shadow);margin-bottom:0.9rem}
    .stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:0.8rem;margin-bottom:1rem}
    .stat-card{background:#fff;border:1px solid var(--border-color);border-radius:0.75rem;padding:0.9rem;box-shadow:var(--shadow);border-top:3px solid #f97316}
    .stat-value{font-size:1.4rem;font-weight:700;color:#f97316;margin-bottom:0.2rem}
    .stat-label{color:var(--text-secondary);font-size:0.75rem}
    .chip{display:inline-flex;align-items:center;gap:0.3rem;padding:0.2rem 0.5rem;border-radius:999px;font-size:0.72rem;border:1px solid var(--border-color);color:#334155;background:#f8fafc}
    .badge{display:inline-block;padding:0.18rem 0.5rem;border-radius:999px;font-size:0.68rem;font-weight:600;background:#fed7aa;color:#01172a}
    .badge-low{background:#ecfeff;color:#155e75}
    .badge-medium{background:#eff6ff;color:#1e3a8a}
    .badge-high{background:#fef2f2;color:#991b1b}
    .table{width:100%;border-collapse:collapse;font-size:0.8125rem}
    .table th,.table td{padding:0.75rem;border-bottom:1px solid #f3f4f6;text-align:left}
    .table thead{background:linear-gradient(135deg,#01172a 0%,#1f2937 100%);color:#ffffff;border-bottom:2px solid #f97316;font-weight:600;font-size:0.75rem}
    .filter-form input,.filter-form select,.filter-form button{font-family:'Poppins',system-ui,Segoe UI,Roboto,Helvetica,Arial,sans-serif}
    </style>";
    echo '</head><body>';

    echo '<aside class="sidebar">'
        .'<div class="sidebar-header"><h1>School LMS</h1><p>Admin Panel</p></div>'
        .'<ul class="sidebar-menu">'
            .'<li><a href="dashboard.php"'.($active==='dashboard'?' class="active"':'').'>
                <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                Dashboard</a></li>'
            .(function() use ($active) {
                $userActive = in_array($active, ['users','students','teachers'], true);
                $parentClass = $userActive ? ' class="has-children open"' : ' class="has-children"';
                return '<li'.$parentClass.'>'
                    .'<a href="#" class="sidebar-parent" data-submenu="users-submenu">
                        <span style="display:flex;align-items:center;gap:.6rem;flex:1;">'
                        .'<svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                        Users</span>'
                        .'<svg class="chevron" viewBox="0 0 24 24" width="18" height="18" style="margin-left:auto;"><polyline points="6 9 12 15 18 9" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>'
                    .'</a>'
                    .'<ul class="sidebar-submenu" id="users-submenu" style="'.($userActive ? 'display:block;' : '').'">'
                        .'<li><a href="users.php"'.($active==='users'?' class="active"':'').'>
                            <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle></svg>
                            All Users</a></li>'
                        .'<li><a href="students.php"'.($active==='students'?' class="active"':'').'>
                            <svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                            Students</a></li>'
                        .'<li><a href="teachers.php"'.($active==='teachers'?' class="active"':'').'>
                            <svg viewBox="0 0 24 24"><path d="M2 12l9-5 9 5-9 5-9-5z"></path><path d="M2 12l9 5 9-5"></path><path d="M2 17l9 5 9-5"></path></svg>
                            Teachers</a></li>'
                    .'</ul>'
                .'</li>';
            })()
            .'<li><a href="courses.php"'.($active==='courses'?' class="active"':'').'>
                <svg viewBox="0 0 24 24"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path></svg>
                Courses</a></li>'
            .'<li><a href="enrollments.php"'.($active==='enrollments'?' class="active"':'').'>
                <svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                Enrollments</a></li>'
            .'<li><a href="reports.php"'.($active==='reports'?' class="active"':'').'>
                <svg viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
                Reports</a></li>'
            .'<li><a href="terms.php"'.($active==='terms'?' class="active"':'').'>
                <svg viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v14c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="2" y1="10" x2="22" y2="10"></line></svg>
                Terms</a></li>'
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
                .'<div style="font-weight:700">' . htmlspecialchars($fullName ?: 'Admin') . '</div>'
                .'<div id="current-datetime" style="color:#64748b"></div>'
            .'</div>'
            .'<div style="width:36px;height:36px;border-radius:50%;background:#e2e8f0;display:flex;align-items:center;justify-content:center;font-weight:700;color:#475569">' . htmlspecialchars($fullName ? strtoupper($fullName[0]) : 'A') . '</div>'
        .'</div></div><div class="content">';
    echo "<script>\n      (function(){\n        function fmt(d){\n          const opts={weekday:'short',year:'numeric',month:'short',day:'numeric',hour:'numeric',minute:'2-digit'};\n          return new Intl.DateTimeFormat(undefined,opts).format(d);\n        }\n        const el=document.getElementById('current-datetime');\n        function tick(){ if(el){ el.textContent = fmt(new Date()); } }\n        tick();\n        setInterval(tick, 30000);\n        document.querySelectorAll('.has-children > a.sidebar-parent').forEach(function(link){\n          link.addEventListener('click', function(e){\n            e.preventDefault();\n            const li = this.parentElement;\n            li.classList.toggle('open');\n            const target = this.getAttribute('data-submenu');\n            const ul = target ? document.getElementById(target) : null;\n            if (ul) { ul.style.display = li.classList.contains('open') ? 'block' : 'none'; }\n          });\n        });\n      })();\n    </script>";
}

function adminLayoutEnd() {
    echo '</div></div>';
    echo "</body></html>";
}
