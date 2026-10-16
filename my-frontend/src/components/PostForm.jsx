import { useEffect, useState } from 'react'
import axios from 'axios'

// onCreated … 保存に成功したときに親（App）へ知らせるための関数
function PostForm({ onCreated }) {
  // フォームの入力値を state で管理する
  const [title, setTitle] = useState('')
  const [content, setContent] = useState('')
  const [categoryId, setCategoryId] = useState('')

  const [categories, setCategories] = useState([])
  const [errors, setErrors] = useState({})
  const [submitting, setSubmitting] = useState(false)

  // カテゴリーの選択肢を API から取得する
  useEffect(() => {
    axios
      .get('http://localhost/api/categories')
      .then((res) => setCategories(res.data.data))
      .catch((err) => console.error(err))
  }, [])

  const handleSubmit = (e) => {
    // form のデフォルト動作（ページのリロード）を止める
    e.preventDefault()

    setSubmitting(true)
    setErrors({})

    axios
      .post('http://localhost/api/posts', {
        title: title,
        content: content,
        category_id: categoryId,
      })
      .then(() => {
        // 入力欄を空に戻して、親に一覧の再取得を頼む
        setTitle('')
        setContent('')
        setCategoryId('')
        onCreated()
      })
      .catch((err) => {
        // 422 はバリデーションエラー。errors に { 項目名: [メッセージ] } が入っている
        if (err.response?.status === 422) {
          setErrors(err.response.data.errors)
        } else {
          console.error(err)
          setErrors({ general: ['保存に失敗しました'] })
        }
      })
      .finally(() => {
        setSubmitting(false)
      })
  }

  return (
    <form onSubmit={handleSubmit}>
      <h2>新規投稿</h2>

      {errors.general && <p>{errors.general[0]}</p>}

      <div>
        <label>
          タイトル
          <input
            type="text"
            value={title}
            onChange={(e) => setTitle(e.target.value)}
          />
        </label>
        {errors.title && <p>{errors.title[0]}</p>}
      </div>

      <div>
        <label>
          カテゴリー
          <select
            value={categoryId}
            onChange={(e) => setCategoryId(e.target.value)}
          >
            <option value="">選択してください</option>
            {categories.map((category) => (
              <option key={category.id} value={category.id}>
                {category.name}
              </option>
            ))}
          </select>
        </label>
        {errors.category_id && <p>{errors.category_id[0]}</p>}
      </div>

      <div>
        <label>
          内容
          <textarea
            value={content}
            onChange={(e) => setContent(e.target.value)}
          />
        </label>
        {errors.content && <p>{errors.content[0]}</p>}
      </div>

      {/* 送信中はボタンを無効にして二重送信を防ぐ */}
      <button type="submit" disabled={submitting}>
        {submitting ? '送信中...' : '投稿する'}
      </button>
    </form>
  )
}

export default PostForm
