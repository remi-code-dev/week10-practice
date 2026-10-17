import { useEffect, useState } from 'react'
import api from './api'
import PostForm from './components/PostForm'
import PostList from './components/PostList'

// App は「データの持ち主」。posts を取得・保持し、子コンポーネントに配る
function App() {
  const [posts, setPosts] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)

  // 一覧を取得する処理を関数にまとめて、初回表示と保存後の両方で使い回す
  const fetchPosts = () => {
    api
      .get('/posts')
      .then((res) => {
        // res.data       … axios が受け取ったレスポンス本体
        // res.data.data  … Laravel の Resource が包んだ配列
        setPosts(res.data.data)
        setError(null)
      })
      .catch((err) => {
        console.error(err)
        setError('データの取得に失敗しました')
      })
      .finally(() => {
        setLoading(false)
      })
  }

  // 第2引数が [] なので、最初の表示時に1回だけ実行される
  useEffect(() => {
    fetchPosts()
  }, [])

  return (
    <div>
      {/* 保存に成功したら fetchPosts が呼ばれ、一覧が最新になる */}
      <PostForm onCreated={fetchPosts} />

      <h1>投稿一覧</h1>
      <PostList posts={posts} loading={loading} error={error} />
    </div>
  )
}

export default App
