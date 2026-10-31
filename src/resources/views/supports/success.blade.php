@extends('layouts.blog')

@section('title', '応援ありがとうございました')

@section('content')
    <div class="card">
        <div class="card-body text-center">
            <h1 class="card-title h3">応援ありがとうございました</h1>
            <p class="card-text">あなたの応援は書き手に届きました。</p>
            <a href="{{ route('posts.index') }}" class="btn btn-primary">記事一覧に戻る</a>
        </div>
    </div>
@endsection
