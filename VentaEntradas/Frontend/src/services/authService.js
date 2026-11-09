import api from './api';

// POST /auth/login { username, password } -> { token, user }
// GET  /auth/me                           -> { user }
export default {
    login(username, password) {
        return api.post('/auth/login', { username, password });
    },
    me() {
        return api.get('/auth/me');
    }
};
