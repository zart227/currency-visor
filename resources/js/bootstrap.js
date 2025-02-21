import axios from 'axios';

window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.headers.common['Accept'] = 'application/json';

const token = document.querySelector('meta[name="csrf-token"]');

if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.getAttribute('content');
} else {
    console.error('CSRF token not found: https://laravel.com/docs/csrf#csrf-x-csrf-token');
}

// Устанавливаем базовый URL для всех запросов
window.axios.defaults.baseURL = window.location.origin;

// Добавляем перехватчик ответов для обработки ошибок
window.axios.interceptors.response.use(
    response => response,
    error => {
        if (error.response?.status === 419) {
            // Если токен CSRF истек, перезагружаем страницу
            window.location.reload()
        }
        return Promise.reject(error)
    }
)
