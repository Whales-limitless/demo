<?php
// Check if user is logged in via cookies
$isLoggedIn = true;


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - AsyncTech HRMS</title>
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="/hrmsweb/manifest.json">
    
    <!-- iOS specific meta tags -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <meta name="apple-mobile-web-app-title" content="HRMS">
    <link rel="apple-touch-icon" href="https://picsum.photos/152/152">
    
    <!-- Theme color for address bar -->
    <meta name="theme-color" content="#233446">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #f5f5f9;
            --secondary-color: #233446;
        }
        
        body {
            background: linear-gradient(to bottom, rgba(35, 52, 70, 0.05), white);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            padding-bottom: 80px;
        }
        
        .app-header {
            background: var(--secondary-color);
            color: white;
            padding: 15px 0;
            margin-bottom: 0;
        }
        
        .user-header {
            background: white;
            border-radius: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            padding: 20px;
            margin: 20px;
            margin-bottom: 25px;
        }
        
        .user-info h5 {
            color: var(--secondary-color);
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .user-info p {
            color: #6c757d;
            margin-bottom: 0;
        }
        
        .notification-badge {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            border: 2px solid var(--secondary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--secondary-color);
            font-weight: bold;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .notification-badge:hover {
            background: var(--secondary-color);
            color: white;
        }
        
        .company-logo {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }
        
        .logo-container {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 8px;
            background: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .logo-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .company-name {
            font-size: 12px;
            color: #6c757d;
            max-width: 70px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .stats-grid {
            padding: 0 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, var(--secondary-color), #1a252f);
            border-radius: 20px;
            padding: 20px;
            color: white;
            text-decoration: none;
            display: block;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(35, 52, 70, 0.2);
            margin-bottom: 15px;
            min-height: 140px;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(35, 52, 70, 0.3);
            color: white;
            text-decoration: none;
        }
        
        .stat-card.loading {
            background: linear-gradient(135deg, #6c757d, #495057);
            pointer-events: none;
        }
        
        .stat-card i {
            font-size: 44px;
            margin-bottom: 12px;
            opacity: 0.9;
        }
        
        .stat-card h6 {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 8px;
            letter-spacing: 0.5px;
        }
        
        .stat-card .subtitle {
            font-size: 14px;
            opacity: 0.9;
            font-weight: 500;
        }
        
        .stat-card.attendance {
            background: linear-gradient(135deg, #4f46e5, #3730a3);
        }
        .stat-card.advance {
            background: linear-gradient(135deg, #059669, #047857);
        }
        .stat-card.leave {
            background: linear-gradient(135deg, #dc2626, #b91c1c);
        }
        .stat-card.others {
            background: linear-gradient(135deg, #7c3aed, #6d28d9);
        }
        
        .loading-overlay {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            display: none;
        }
        
        .stat-card.loading .loading-overlay {
            display: block;
        }
        
        .stat-card.loading .card-content {
            opacity: 0.3;
        }
        
        .notice-section {
            padding: 0 20px;
            margin-bottom: 30px;
        }
        
        .section-title {
            color: var(--secondary-color);
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 15px;
            padding-left: 4px;
        }
        
        .notice-scroll {
            overflow-x: auto;
            padding-bottom: 12px;
        }
        
        .notice-cards {
            display: flex;
            gap: 12px;
            min-width: max-content;
        }
        
        .notice-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            width: 180px;
            flex-shrink: 0;
            cursor: pointer;
            transition: all 0.3s ease;
            overflow: hidden;
        }
        
        .notice-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }
        
        .notice-thumbnail {
            width: 100%;
            height: 120px;
            background: var(--primary-color);
            position: relative;
            overflow: hidden;
        }
        
        .notice-thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .pdf-badge {
            position: absolute;
            top: 6px;
            right: 6px;
            background: rgba(35, 52, 70, 0.8);
            color: white;
            padding: 2px 6px;
            border-radius: 8px;
            font-size: 10px;
            font-weight: bold;
        }
        
        .notice-content {
            padding: 12px;
        }
        
        .notice-title {
            font-size: 14px;
            font-weight: 600;
            color: var(--secondary-color);
            margin-bottom: 5px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .notice-date {
            font-size: 12px;
            color: #6c757d;
        }
        
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            box-shadow: 0 -4px 15px rgba(0,0,0,0.1);
            padding: 12px 0;
            z-index: 1000;
        }
        
        .nav-items {
            display: flex;
            justify-content: space-around;
            align-items: center;
        }
        
        .nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            color: #6c757d;
            text-decoration: none;
            padding: 8px;
            border-radius: 12px;
            transition: all 0.3s ease;
            min-width: 60px;
        }
        
        .nav-item.active {
            color: var(--secondary-color);
            background: var(--primary-color);
        }
        
        .nav-item i {
            font-size: 20px;
            margin-bottom: 4px;
        }
        
        .nav-item span {
            font-size: 12px;
            font-weight: 500;
        }
        
        .loading-state {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }
        
        .modal-header {
            background: var(--secondary-color);
            color: white;
            border-radius: 15px 15px 0 0;
        }
        
        .modal-content {
            border-radius: 15px;
        }
        
        .refresh-indicator {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(35, 52, 70, 0.9);
            color: white;
            padding: 20px;
            border-radius: 15px;
            z-index: 9999;
            display: none;
        }
        
    </style>
</head>
<body>
    <!-- App Header -->
    <div class="app-header">
        <div class="container text-center">
            <h5 class="mb-0">Dashboard</h5>
        </div>
    </div>
    
    <!-- User Header -->
    <div class="user-header">
        <div class="row align-items-center">
            <div class="col-8">
                <div class="user-info">
                    <h5 id="userName">Loading...</h5>
                    <p>Welcome back to your dashboard</p>
                </div>
            </div>
            <div class="col-4">
                <div class="d-flex justify-content-end align-items-center gap-2">
                    <div class="notification-badge" onclick="openNotifications()">
                        <span id="notificationCount">0</span>
                    </div>
                    <div class="company-logo">
                        <div class="logo-container" id="logoContainer">
                            <i class="fas fa-image text-muted"></i>
                        </div>
                        <div class="company-name" id="companyName">Loading...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Stats Grid -->
    <div class="stats-grid">
        <div class="row">
            <div class="col-6">
                <a href="attendance.php" class="stat-card attendance" id="attendanceCard">
                    <div class="card-content">
                        <i class="fas fa-clock"></i>
                        <h6>Attendance</h6>
                        <div class="subtitle" id="attendanceStatus">Loading...</div>
                    </div>
                    <div class="loading-overlay">
                        <i class="fas fa-spinner fa-spin text-white"></i>
                    </div>
                </a>
            </div>
            <div class="col-6">
                <a href="advance.php" class="stat-card advance" id="advanceCard">
                    <div class="card-content">
                        <i class="fas fa-dollar-sign"></i>
                        <h6>Advance</h6>
                        <div class="subtitle" id="advanceStatus">Loading...</div>
                    </div>
                    <div class="loading-overlay">
                        <i class="fas fa-spinner fa-spin text-white"></i>
                    </div>
                </a>
            </div>
            <div class="col-6">
                <a href="leavepage.php" class="stat-card leave" id="leaveCard">
                    <div class="card-content">
                        <i class="fas fa-calendar-alt"></i>
                        <h6>Leave</h6>
                        <div class="subtitle" id="leaveStatus">Loading...</div>
                    </div>
                    <div class="loading-overlay">
                        <i class="fas fa-spinner fa-spin text-white"></i>
                    </div>
                </a>
            </div>
            <div class="col-6">
                <a href="#" class="stat-card others" id="othersCard" onclick="showOthersModal()">
                    <div class="card-content">
                        <i class="fas fa-ellipsis-h"></i>
                        <h6>Others</h6>
                        <div class="subtitle">More options</div>
                    </div>
                </a>
            </div>
        </div>
    </div>
    
    <!-- Notice Section -->
    <div class="notice-section">
        <div class="section-title">Company Notice Board</div>
        <div class="notice-scroll">
            <div class="notice-cards" id="noticeCards">
                <!-- Notice cards will be loaded here -->
            </div>
        </div>
    </div>
    
    <!-- Bottom Navigation -->
    <div class="bottom-nav">
        <div class="nav-items">
            <a href="index.php" class="nav-item active">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
            <a href="#" class="nav-item" onclick="openNotifications()">
                <i class="fas fa-bell"></i>
                <span>Alerts</span>
            </a>
            <a href="profile.php" class="nav-item">
                <i class="fas fa-user"></i>
                <span>Profile</span>
            </a>
        </div>
    </div>
    
    <!-- Others Modal -->
    <div class="modal fade" id="othersModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Others</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="d-grid gap-3">
                        <button class="btn btn-outline-primary btn-lg" onclick="window.location.href='overtime.php'">
                            <i class="fas fa-clock me-2"></i> Overtime
                        </button>
                        <button class="btn btn-outline-success btn-lg" onclick="window.location.href='claim.php'">
                            <i class="fas fa-file-invoice me-2"></i> Claim
                        </button>
                        <button class="btn btn-outline-warning btn-lg" onclick="window.location.href='documents.php'">
                            <i class="fas fa-folder me-2"></i> My Documents
                        </button>
                        <button class="btn btn-outline-danger btn-lg" onclick="window.location.href='payment_confirmation.php'">
                            <i class="fas fa-credit-card me-2"></i> Payment Confirmation
                        </button>
                        <button class="btn btn-outline-info btn-lg" onclick="window.location.href='letter.php'">
                            <i class="fas fa-envelope me-2"></i> Letter
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Refresh Indicator -->
    <div class="refresh-indicator" id="refreshIndicator">
        <i class="fas fa-spinner fa-spin me-2"></i> Refreshing...
    </div>
    

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let userData = {};
        let attendanceTimer = null;
        
        // PWA Debug logging function (can be removed in production)
        function debugLog(message) {
            console.log('[PWA] ' + message);
        }
        
        // Initialize PWA functionality
        function initializePWA() {
            debugLog('Starting PWA initialization...');
            debugLog('User Agent: ' + navigator.userAgent);
            debugLog('HTTPS: ' + (location.protocol === 'https:' ? 'YES' : 'NO'));
            debugLog('Service Worker Support: ' + ('serviceWorker' in navigator ? 'YES' : 'NO'));
            
            // Register service worker
            if ('serviceWorker' in navigator) {
                debugLog('Attempting to register service worker...');
                navigator.serviceWorker.register('/hrmsweb/sw.js')
                    .then(registration => {
                        debugLog('SW registered successfully: ' + registration.scope);
                    })
                    .catch(registrationError => {
                        debugLog('SW registration failed: ' + registrationError.message);
                    });
            }
            
            // Check manifest loading
            fetch('/hrmsweb/manifest.json')
                .then(response => {
                    if (response.ok) {
                        debugLog('Manifest file accessible');
                        return response.json();
                    } else {
                        throw new Error('Manifest not found');
                    }
                })
                .then(manifest => {
                    debugLog('Manifest parsed successfully: ' + manifest.name);
                })
                .catch(error => {
                    debugLog('Manifest ERROR: ' + error.message);
                });
        }
        
        
        document.addEventListener('DOMContentLoaded', function() {
            loadUserData();
            initializeData();
            
            // PWA initialization
            initializePWA();
            
            // Pull to refresh simulation
            let startY = 0;
            let isRefreshing = false;
            
            document.addEventListener('touchstart', function(e) {
                startY = e.touches[0].clientY;
            });
            
            document.addEventListener('touchmove', function(e) {
                if (window.scrollY === 0 && e.touches[0].clientY > startY + 100 && !isRefreshing) {
                    isRefreshing = true;
                    refreshDashboard();
                }
            });
        });
        
        function loadUserData() {
            // Get user data from cookies using the new names
            userData.code = getCookie('hrmswebusercode');
            userData.name = getCookie('hrmswebcookiename');
            userData.username = getCookie('hrmswebusername');
            userData.company = getCookie('hrmswebcompany_detector');
            userData.department = getCookie('hrmswebdepartment');
            
            document.getElementById('userName').textContent = `Hello, ${userData.name}!`;
            document.getElementById('companyName').textContent = userData.company || 'Company';
        }
        
        // Helper function to get cookie
        function getCookie(name) {
            const nameEQ = name + '=';
            const ca = document.cookie.split(';');
            for (let i = 0; i < ca.length; i++) {
                let c = ca[i];
                while (c.charAt(0) === ' ') c = c.substring(1, c.length);
                if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
            }
            return null;
        }
        
        // Helper function to delete cookie
        function deleteCookie(name) {
            document.cookie = name + '=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
        }
        
        async function initializeData() {
            const promises = [
                fetchCompanyLogo(),
                fetchAttendanceType(),
                fetchAdvanceStatus(),
                fetchLeaveStatus(),
                fetchNotificationCount(),
                fetchNotices()
            ];
            
            await Promise.all(promises);
            checkPaymentConfirmation();
            checkLetterConfirmation();
        }
        
        async function refreshDashboard() {
            const indicator = document.getElementById('refreshIndicator');
            indicator.style.display = 'block';
            
            try {
                await initializeData();
            } catch (error) {
                console.error('Error refreshing dashboard:', error);
            }
            
            setTimeout(() => {
                indicator.style.display = 'none';
            }, 1000);
        }
        
        async function fetchCompanyLogo() {
            try {
                const formData = new FormData();
                formData.append('company', userData.company);
                formData.append('code', userData.code);
                
                const response = await fetch('https://ipqsync.me/hrms/api_fetch_company.php', {
                    method: 'POST',
                    body: formData
                });
                
                if (response.ok) {
                    const data = await response.json();
                    if (data.success && data.data && data.data.logo) {
                        document.getElementById('logoContainer').innerHTML = `
                            <img src="${data.data.logo}" alt="Company Logo" 
                                 onerror="this.parentElement.innerHTML='<i class=&quot;fas fa-building text-muted&quot;></i>'">
                        `;
                    }
                }
            } catch (error) {
                console.error('Error fetching logo:', error);
            }
        }
        
        async function fetchAttendanceType() {
            const card = document.getElementById('attendanceCard');
            card.classList.add('loading');
            
            try {
                const formData = new FormData();
                formData.append('code', userData.code);
                formData.append('company', userData.company);
                
                const response = await fetch('https://ipqsync.me/hrms/attendance/api_fetch_type.php', {
                    method: 'POST',
                    body: formData
                });
                
                if (response.ok) {
                    const data = await response.json();
                    if (data.status === 'success') {
                        const attntype = data.attntype;
                        const stime = data.stime;
                        
                        if (attntype && (attntype.startsWith('WORK_IN') || attntype.startsWith('BREAK_IN')) && stime) {
                            startAttendanceTimer(stime);
                        } else {
                            document.getElementById('attendanceStatus').textContent = 'Not IN';
                        }
                    }
                }
            } catch (error) {
                console.error('Error fetching attendance:', error);
                document.getElementById('attendanceStatus').textContent = 'Error';
            }
            
            card.classList.remove('loading');
        }
        
        function startAttendanceTimer(startTimeString) {
            try {
                const startTime = new Date(startTimeString);
                
                if (attendanceTimer) {
                    clearInterval(attendanceTimer);
                }
                
                attendanceTimer = setInterval(() => {
                    const now = new Date();
                    const diff = now - startTime;
                    
                    if (diff > 0) {
                        const hours = Math.floor(diff / (1000 * 60 * 60));
                        const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                        const seconds = Math.floor((diff % (1000 * 60)) / 1000);
                        
                        document.getElementById('attendanceStatus').textContent = 
                            `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                    }
                }, 1000);
            } catch (error) {
                console.error('Error starting timer:', error);
                document.getElementById('attendanceStatus').textContent = 'Invalid time';
            }
        }
        
        async function fetchAdvanceStatus() {
            const card = document.getElementById('advanceCard');
            card.classList.add('loading');
            
            try {
                const formData = new FormData();
                formData.append('code', userData.code);
                formData.append('company', userData.company);
                
                const response = await fetch('https://ipqsync.me/hrms/advance/api_fetch_control.php', {
                    method: 'POST',
                    body: formData
                });
                
                if (response.ok) {
                    const data = await response.json();
                    if (data.status === 'success') {
                        document.getElementById('advanceStatus').textContent = 
                            data.form_status === 'open' ? 'Open' : 'Close';
                    }
                }
            } catch (error) {
                console.error('Error fetching advance status:', error);
                document.getElementById('advanceStatus').textContent = 'Error';
            }
            
            card.classList.remove('loading');
        }
        
        async function fetchLeaveStatus() {
            const card = document.getElementById('leaveCard');
            card.classList.add('loading');
            
            try {
                const formData = new FormData();
                formData.append('code', userData.code);
                formData.append('company', userData.company);
                
                const response = await fetch('https://ipqsync.me/hrms/leave/api_remaining_al.php', {
                    method: 'POST',
                    body: formData
                });
                
                if (response.ok) {
                    const data = await response.json();
                    if (data.status === 'success') {
                        const remainingAL = parseFloat(data.remainingAL);
                        document.getElementById('leaveStatus').textContent = `AL: ${remainingAL.toFixed(1)}`;
                    }
                }
            } catch (error) {
                console.error('Error fetching leave status:', error);
                document.getElementById('leaveStatus').textContent = 'Error';
            }
            
            card.classList.remove('loading');
        }
        
        async function fetchNotificationCount() {
            try {
                const formData = new FormData();
                formData.append('code', userData.code);
                formData.append('department', userData.department);
                formData.append('company', userData.company);
                
                const response = await fetch('https://ipqsync.me/hrms/notification/api_fetch_unread_count.php', {
                    method: 'POST',
                    body: formData
                });
                
                if (response.ok) {
                    const data = await response.json();
                    if (data.status === 'success') {
                        document.getElementById('notificationCount').textContent = data.count;
                    }
                }
            } catch (error) {
                console.error('Error fetching notifications:', error);
            }
        }
        
        async function fetchNotices() {
            try {
                const formData = new FormData();
                formData.append('code', userData.code);
                formData.append('company', userData.company);
                
                const response = await fetch('https://ipqsync.me/hrms/noticeboard/api_fetch_noticeboard.php', {
                    method: 'POST',
                    body: formData
                });
                
                if (response.ok) {
                    const data = await response.json();
                    if (data.status === 'success') {
                        displayNotices(data.data);
                    }
                }
            } catch (error) {
                console.error('Error fetching notices:', error);
                document.getElementById('noticeCards').innerHTML = `
                    <div class="text-center p-4 text-muted">
                        <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                        <p>Error loading notices</p>
                    </div>
                `;
            }
        }
        
        function displayNotices(notices) {
            const container = document.getElementById('noticeCards');
            
            if (notices.length === 0) {
                container.innerHTML = `
                    <div class="text-center p-4 text-muted">
                        <i class="fas fa-info-circle fa-2x mb-2"></i>
                        <p>No notices available</p>
                    </div>
                `;
                return;
            }
            
            container.innerHTML = notices.map(notice => {
                const date = new Date(notice.date_created);
                const formattedDate = date.toLocaleDateString('en-US', { 
                    month: 'short', 
                    day: 'numeric', 
                    year: 'numeric' 
                });
                
                return `
                    <div class="notice-card" onclick="openPDF('${notice.file_path}')">
                        <div class="notice-thumbnail">
                            <img src="${notice.thumbnail_path}" alt="Notice thumbnail" 
                                 onerror="this.parentElement.innerHTML='<div class=&quot;d-flex align-items-center justify-content-center h-100&quot;><i class=&quot;fas fa-file-pdf fa-2x text-muted&quot;></i></div>'">
                            <div class="pdf-badge">PDF</div>
                        </div>
                        <div class="notice-content">
                            <div class="notice-title">${notice.title}</div>
                            <div class="notice-date">${formattedDate}</div>
                        </div>
                    </div>
                `;
            }).join('');
        }
        
        async function checkPaymentConfirmation() {
            try {
                const formData = new FormData();
                formData.append('code', userData.code);
                formData.append('company', userData.company);
                
                const response = await fetch('https://ipqsync.me/hrms/payroll/payroll_confirmation/api_check_payment_confirmation.php', {
                    method: 'POST',
                    body: formData
                });
                
                if (response.ok) {
                    const data = await response.json();
                    if (data.status === 'success' && data.user_status === '') {
                        showPaymentDialog(data.year, data.month);
                    }
                }
            } catch (error) {
                console.error('Error checking payment confirmation:', error);
            }
        }
        
        async function checkLetterConfirmation() {
            try {
                const formData = new FormData();
                formData.append('code', userData.code);
                formData.append('company', userData.company);
                
                const response = await fetch('https://ipqsync.me/hrms/letter/api_check_letter_confirmation.php', {
                    method: 'POST',
                    body: formData
                });
                
                if (response.ok) {
                    const data = await response.json();
                    if (data.status === 'success' && (!data.letter_status || data.letter_status === '')) {
                        showLetterDialog(data.title || data.description || 'Untitled', data.date || 'Unknown Date');
                    }
                }
            } catch (error) {
                console.error('Error checking letter confirmation:', error);
            }
        }
        
        function showPaymentDialog(year, month) {
            if (confirm(`You have a pending payment confirmation for ${month} ${year}. Go to payment confirmation page?`)) {
                // Redirect to the actual payment confirmation page
                window.location.href = 'payment_confirmation.php';
            }
        }
        
        function showLetterDialog(title, date) {
            if (confirm(`You have a pending letter confirmation for "${title}" issued on ${date}. Go to letter page?`)) {
                // Redirect to the actual letter page
                window.location.href = 'letter.php';
            }
        }
        
        function openPDF(pdfUrl) {
            window.open(pdfUrl, '_blank');
        }
        
        function showOthersModal() {
            new bootstrap.Modal(document.getElementById('othersModal')).show();
        }
        
        function openNotifications() {
            window.location.href = 'notification.php';
        }
        
        async function logout() {
            try {
                // Clear all HRMS cookies with the new prefix
                const cookiesToClear = [
                    'hrmswebusercode',
                    'hrmswebusername', 
                    'hrmswebcookiename',
                    'hrmswebcompany_detector',
                    'hrmswebdepartment',
                    'hrmswebrole',
                    'hrmswebstafftype'
                ];
                
                cookiesToClear.forEach(cookieName => {
                    deleteCookie(cookieName);
                });
                
                // Redirect to login page
                window.location.href = 'login.php';
            } catch (error) {
                console.error('Error logging out:', error);
                // Fallback: redirect to login page
                window.location.href = 'login.php';
            }
        }
    </script>
</body>
</html>