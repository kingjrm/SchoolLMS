<?php
/**
 * Shared Student Layout to keep sidebar/topbar consistent with dashboard
 */

function studentLayoutStart(string $active, string $title, bool $showJoinButton = true) {
    global $SHOW_JOIN_BUTTON;
    $SHOW_JOIN_BUTTON = $showJoinButton;
    
    echo '<!DOCTYPE html><html lang="en"><head>';
    echo '<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<title>' . htmlspecialchars($title) . ' - School LMS</title>';
    echo '<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">';
    echo "<style>
        *{margin:0;padding:0;box-sizing:border-box}
        :root{--bg-primary:#ffffff;--bg-secondary:#f6f7fb;--bg-sidebar:#01172a;--text-primary:#01172a;--text-secondary:#6b7280;--border-color:#e5e7eb;--shadow:0 1px 3px rgba(0,0,0,.08);--shadow-lg:0 10px 25px rgba(0,0,0,.08);--primary-color:#f97316;--primary-dark:#ea580c}
        body{font-family:'Poppins',system-ui,Segoe UI,Roboto,Helvetica,Arial,sans-serif;background:var(--bg-secondary);color:var(--text-primary);display:flex;min-height:100vh}
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
        .card{background:var(--bg-primary);border:1px solid var(--border-color);border-radius:1rem;padding:1.1rem;box-shadow:var(--shadow);margin-bottom:1rem}
        .stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:0.8rem;margin-bottom:1rem}
        .stat-card{background:#fff;border:1px solid var(--border-color);border-radius:0.75rem;padding:0.9rem;box-shadow:var(--shadow);border-top:3px solid #f97316}
        .stat-value{font-size:1.4rem;font-weight:700;color:#f97316;margin-bottom:0.2rem}
        .stat-label{color:var(--text-secondary);font-size:0.75rem}
        .chip{display:inline-flex;align-items:center;gap:0.3rem;padding:0.2rem 0.5rem;border-radius:999px;font-size:0.7rem;border:1px solid var(--border-color);color:#334155;background:#f8fafc}
        .badge{display:inline-block;padding:0.2rem 0.5rem;border-radius:999px;font-size:0.65rem;font-weight:600;background:#fed7aa;color:#01172a}
        .badge-low{background:#ecfeff;color:#155e75}
        .badge-medium{background:#eff6ff;color:#1e3a8a}
        .badge-high{background:#fef2f2;color:#991b1b}
        .table{width:100%;border-collapse:collapse;font-size:0.8rem}
        .table th,.table td{padding:0.6rem;border-bottom:1px solid #f3f4f6;text-align:left}
        .table thead{background:linear-gradient(135deg,#01172a 0%,#1f2937 100%);color:#ffffff;border-bottom:2px solid #f97316;font-weight:600;font-size:0.75rem}
    </style>";
    echo '</head><body>';

    echo '<aside class="sidebar">'
        .'<div class="sidebar-header"><h1>School LMS</h1><p>Student Portal</p></div>'
        .'<ul class="sidebar-menu">'
            .'<li><a href="dashboard.php"'.($active==='dashboard'?' class="active"':'').'>
                <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                Dashboard</a></li>'
            .'<li><a href="courses.php"'.($active==='courses'?' class="active"':'').'>
                <svg viewBox="0 0 24 24"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path></svg>
                Courses</a></li>'
            .'<li><a href="assignments.php"'.($active==='assignments'?' class="active"':'').'>
                <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                Assignments</a></li>'
            .'<li><a href="grades.php"'.($active==='grades'?' class="active"':'').'>
                <svg viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
                Grades</a></li>'
            .'<li><a href="announcements.php"'.($active==='announcements'?' class="active"':'').'>
                <svg viewBox="0 0 24 24"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                Announcements</a></li>'
            .'<li><a href="tasks.php"'.($active==='tasks'?' class="active"':'').'>
                <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect></svg>
                Tasks</a></li>'
        .'</ul>'
        .'<div class="sidebar-footer">'
            .'<div class="footer-links">'
                .'<a class="footer-link" href="settings.php">'
                    .'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09a1.65 1.65 0 0 0-1-1.51 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09a1.65 1.65 0 0 0 1.51-1 1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9c0 .69.28 1.35.78 1.82.31.3.31.78 0 1.09A2.64 2.64 0 0 0 19.4 15z"/></svg>'
                    .'Settings'
                .'</a>'
                .'<a class="footer-link" href="help.php">'
                    .'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.82 1c0 2-3 2-3 4"/><line x1="12" y1="17" x2="12" y2="17"/></svg>'
                    .'Help'
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
                .'<div style="font-weight:700">' . htmlspecialchars($fullName ?: 'Student') . '</div>'
                .'<div id="current-datetime" style="color:#64748b"></div>'
            .'</div>'
            .'<div style="width:36px;height:36px;border-radius:50%;background:#e2e8f0;display:flex;align-items:center;justify-content:center;font-weight:700;color:#475569">' . htmlspecialchars($fullName ? strtoupper($fullName[0]) : 'S') . '</div>'
        .'</div></div><div class="content">';
    echo "<script>\n      (function(){\n        function fmt(d){\n          const opts={weekday:'short',year:'numeric',month:'short',day:'numeric',hour:'numeric',minute:'2-digit'};\n          return new Intl.DateTimeFormat(undefined,opts).format(d);\n        }\n        const el=document.getElementById('current-datetime');\n        function tick(){ if(el){ el.textContent = fmt(new Date()); } }\n        tick();\n        setInterval(tick, 30000);\n      })();\n    </script>";
}

function studentLayoutEnd() {
    global $SHOW_JOIN_BUTTON;
    $showJoinButton = $SHOW_JOIN_BUTTON ?? true;
    
    echo '</div></div>';
    
    // Floating Join Button - Outside content div so fixed positioning works
    echo '
    <style>
        .fab-join {
            position: fixed !important;
            right: 24px !important;
            bottom: 24px !important;
            width: 56px !important;
            height: 56px !important;
            border-radius: 50% !important;
            border: none !important;
            background: #f97316 !important;
            color: #fff !important;
            font-size: 28px !important;
            font-weight: 700 !important;
            box-shadow: 0 10px 25px rgba(249, 115, 22, 0.35) !important;
            cursor: pointer !important;
            transition: transform 0.15s ease, background 0.2s ease, box-shadow 0.2s ease !important;
            z-index: 9999 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            visibility: visible !important;
        }
        .fab-join:hover { background: #ea580c !important; transform: translateY(-2px) !important; box-shadow: 0 12px 28px rgba(234, 88, 12, 0.4) !important; }
        .fab-join:active { transform: translateY(0) !important; }

        .join-modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(17,24,39,0.55);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9998;
        }
        .join-modal {
            width: 100%;
            max-width: 460px;
            background: #ffffff;
            border-radius: 0.75rem;
            overflow: hidden;
            box-shadow: 0 20px 45px rgba(0,0,0,0.2);
            transform: translateY(8px);
            opacity: 0;
            transition: all 0.2s ease;
        }
        .join-modal.show { transform: translateY(0); opacity: 1; }
        .join-modal-header { padding: 1rem 1.25rem; border-bottom: 2px solid #f97316; display: flex; justify-content: space-between; align-items: center; }
        .join-modal-title { margin: 0; font-size: 1rem; font-weight: 700; color: #01172a; }
        .join-close { background: transparent; border: none; font-size: 1.25rem; cursor: pointer; color: #6b7280; }
        .join-modal-body { padding: 1.25rem; }
        .join-input { width: 100%; padding: 0.875rem 1rem; border: 1.5px solid #e5e7eb; border-radius: 0.5rem; font-size: 1rem; outline: none; }
        .join-input:focus { border-color: #f97316; box-shadow: 0 0 0 3px rgba(249,115,22,0.15); }
        .join-help { font-size: 0.8125rem; color: #6b7280; margin-top: 0.5rem; }
        .join-actions { display: flex; gap: 0.5rem; justify-content: flex-end; padding: 0 1.25rem 1.25rem; }
        .btn-secondary { background: #f1f5f9; color: #374151; }
        .btn-secondary:hover { background: #e5e7eb; }
        .btn-primary { background: #f97316; color: #fff; }
        .btn-primary:hover { background: #ea580c; }
    </style>

    <button type="button" class="fab-join" id="openJoinModal" aria-label="Join course"' . ($showJoinButton ? '' : ' style="display:none"') . '>+</button>

    <div class="join-modal-backdrop" id="joinModalBackdrop" aria-hidden="true">
        <div class="join-modal" id="joinModal">
            <div class="join-modal-header">
                <h3 class="join-modal-title">Join Course with Code</h3>
                <button type="button" class="join-close" id="closeJoinModal" aria-label="Close">×</button>
            </div>
            <form method="POST">
                <div class="join-modal-body">
                    <label style="display:block; font-size: 0.875rem; color:#374151; margin-bottom:0.5rem; font-weight:600;">Course Code</label>
                    <input class="join-input" type="text" name="join_code" id="joinCodeInput" placeholder="e.g., ABC123" maxlength="10" required>
                    <div class="join-help">Ask your instructor for the join code.</div>
                </div>
                <div class="join-actions">
                    <button type="button" class="btn btn-secondary" id="cancelJoin">Cancel</button>
                    <button type="submit" class="btn btn-primary" name="join_by_code" value="1">Join</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    (function(){
        const fab = document.getElementById("openJoinModal");
        const backdrop = document.getElementById("joinModalBackdrop");
        const modal = document.getElementById("joinModal");
        const closeBtn = document.getElementById("closeJoinModal");
        const cancelBtn = document.getElementById("cancelJoin");
        const codeInput = document.getElementById("joinCodeInput");

        function openModal(){
            backdrop.style.display = "flex";
            requestAnimationFrame(() => modal.classList.add("show"));
            setTimeout(() => codeInput && codeInput.focus(), 150);
        }
        function closeModal(){
            modal.classList.remove("show");
            setTimeout(() => { backdrop.style.display = "none"; }, 150);
        }
        function clickOutside(e){ if(e.target === backdrop) closeModal(); }

        if (fab) fab.addEventListener("click", openModal);
        if (closeBtn) closeBtn.addEventListener("click", closeModal);
        if (cancelBtn) cancelBtn.addEventListener("click", closeModal);
        if (backdrop) backdrop.addEventListener("click", clickOutside);
        if (codeInput) codeInput.addEventListener("input", () => { codeInput.value = codeInput.value.toUpperCase(); });
        document.addEventListener("keydown", (e) => { if (e.key === "Escape" && backdrop.style.display === "flex") closeModal(); });
    })();
    </script>
    ';
    
    echo "<script>\n"
        . "(function(){\n"
        . "  if(typeof window.__lmsSessionId==='undefined'){\n"
        . "    window.__lmsSessionId = 'session_' + Date.now() + '_' + Math.random().toString(36).substr(2,9);\n"
        . "    window.__lmsSessionStart = Date.now();\n"
        . "    fetch('../api/time-tracking-start.php', {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({action:'start'})}).catch(()=>{});\n"
        . "  }\n"
        . "  window.addEventListener('beforeunload', function(){\n"
        . "    fetch('../api/time-tracking-end.php', {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({action:'end'})}).catch(()=>{});\n"
        . "  });\n"
        . "})();\n"
        . "</script>\n"
        . "</body></html>";
}
