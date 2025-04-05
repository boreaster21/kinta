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
    
    // サイドバートグルボタンが存在する場合のみイベントリスナーを追加
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('show');
            
            // コンテンツエリアの調整
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
    
    // サイドバーが存在しない場合は何もしない
    if (!sidebar) {
        return;
    }
    
    // ウィンドウサイズが変更された場合、レスポンシブ対応
    window.addEventListener('resize', function() {
        if (window.innerWidth > 992 && sidebar) {
            sidebar.classList.remove('show');
            
            const content = document.querySelector('.admin-content');
            if (content) {
                content.style.marginLeft = '250px';
            }
        }
    });

    // スマートフォンでサイドバーリンククリック時にサイドバーを閉じる
    const sidebarLinks = document.querySelectorAll('.admin-sidebar-link');
    sidebarLinks.forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 992) {
                sidebar.classList.remove('show');
            }
        });
    });
}); 