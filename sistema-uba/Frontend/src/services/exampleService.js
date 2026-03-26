import api from './api';

export default {
    getExamples() {
        return api.get('/example');
    },
    createExample(data) {
        return api.post('/example', data);
    },
    getProtected() {
        return api.get('/example/protected');
    }
};
