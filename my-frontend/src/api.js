import axios from 'axios'

// API の URL は .env の VITE_API_URL で1か所にまとめて管理する
// baseURL を設定しておくと、api.get('/posts') が http://localhost/api/posts へのリクエストになる
const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL,
})

export default api
