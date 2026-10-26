@extends('layouts.blog')

@section('title', '新規投稿')

@section('content')
    <h1 class="mb-4">新規投稿</h1>

    {{-- enctype="multipart/form-data": これがないとブラウザはファイルを送信しない（ファイル名の文字列だけになる） --}}
    <form action="{{ route('posts.store') }}" method="POST" enctype="multipart/form-data">
        @include('posts._form', ['post' => null, 'submitLabel' => '投稿する'])
    </form>
@endsection
