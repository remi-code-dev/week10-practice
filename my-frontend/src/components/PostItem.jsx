// 投稿1件の見た目だけを担当する（state も API 通信も持たない）
function PostItem({ post }) {
  return (
    <li>
      <h2>{post.title}</h2>
      <p>
        投稿者：{post.user?.name ?? '不明'} ／ {post.created_at}
      </p>
      <p>{post.content}</p>
    </li>
  )
}

export default PostItem
