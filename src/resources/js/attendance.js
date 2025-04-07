document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('.attendance-form');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const button = this.querySelector('button');
            const action = button.textContent.trim();
            
            let message = '';
            switch (action) {
                case '退勤':
                    message = '退勤の打刻を行いますか？\n※この操作は取り消せません。';
                    break;
                case '休憩':
                    message = '休憩を開始しますか？';
                    break;
                case '出勤':
                    message = '出勤の打刻を行いますか？';
                    break;
                case '休憩戻':
                    message = '休憩を終了しますか？';
                    break;
            }
            
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });
});
