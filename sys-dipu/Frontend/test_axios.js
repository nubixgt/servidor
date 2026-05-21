import axios from 'axios';
import FormData from 'form-data';

const api = axios.create();
api.interceptors.request.use(config => {
    try {
        config.headers.set('Authorization', 'Bearer 123');
        console.log("set worked");
    } catch(e) {
        console.log("set failed", e.message);
        config.headers['Authorization'] = 'Bearer 123';
    }
    return config;
});

const fd = new FormData();
fd.append('test', '123');

api.post('http://httpbin.org/post', fd)
    .then(res => console.log(res.data.headers))
    .catch(err => console.log(err.message));
