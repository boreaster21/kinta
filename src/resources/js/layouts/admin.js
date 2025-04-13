/**
 * 管理画面のサイドバー制御
 */
document.addEventListener('DOMContentLoaded', function() {
    // ログイン画面ではサイドバーは存在しないため、チェックをして早期リターン
    if (document.querySelector('.admin-auth')) {
        // ログイン画面の場合は何もしない
        return;
    }
    
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.querySelector('.admin-sidebar');
    
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('show');
            
            const content = document.querySelector('.admin-content');
            if (content) {
                if (sidebar.classList.contains('show')) {
                    content.style.marginLeft = '250px';
                } else {
                    content.style.marginLeft = '0';
                }
            }
        });
    }
    
    if (!sidebar) {
        return;
    }
    
    window.addEventListener('resize', function() {
        if (window.innerWidth > 992 && sidebar) {
            sidebar.classList.remove('show');
            
            const content = document.querySelector('.admin-content');
            if (content) {
                content.style.marginLeft = '250px';
            }
        }
    });

    const sidebarLinks = document.querySelectorAll('.admin-sidebar-link');
    sidebarLinks.forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 992) {
                sidebar.classList.remove('show');
            }
        });
    });
});
