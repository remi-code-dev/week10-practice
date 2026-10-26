@extends('layouts.blog')

@section('title', '投稿編集')

@section('content')
    <h1 class="mb-4">投稿編集</h1>

    {{-- 画像を送るので create と同じく multipart/form-data が必要 --}}
    <form action="{{ route('posts.update', $post) }}" method="POST" enctype="multipart/form-data">
        @method('PUT')
        @include('posts._form', ['post' => $post, 'submitLabel' => '更新する'])
    </form>
@endsection
