import axios from 'axios';
import FormData from 'form-data';

const api = axios.create({
    headers: {
        'Content-Type': 'application/json'
    }
});

const fd = new FormData();
fd.append('test', '123');

api.post('http://httpbin.org/post', fd, {
    headers: { 'Content-Type': 'multipart/form-data' }
})
    .then(res => console.log(res.data.headers['Content-Type']))
    .catch(err => console.log(err.message));

api.post('http://httpbin.org/post', fd)
    .then(res => console.log(res.data.headers['Content-Type']))
    .catch(err => console.log(err.message));

