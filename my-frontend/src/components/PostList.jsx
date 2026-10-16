import PostItem from './PostItem'

// 一覧の枠と「読み込み中・エラー・0件」の表示を担当する
// 1件ごとの見た目は PostItem に任せる
function PostList({ posts, loading, error }) {
  if (loading) {
    return <p>読み込み中...</p>
  }

  if (error) {
    return <p>{error}</p>
  }

  if (posts.length === 0) {
    return <p>投稿はまだありません。</p>
  }

  return (
    <ul>
      {posts.map((post) => (
        // key は map で並べる側に付ける
        <PostItem key={post.id} post={post} />
      ))}
    </ul>
  )
}

export default PostList
