@csrf

<div class="mb-3">
    <label for="title" class="form-label">タイトル</label>
    <input type="text" name="title" id="title"
           class="form-control @error('title') is-invalid @enderror"
           value="{{ old('title', $post->title ?? '') }}">
    @error('title')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="category_id" class="form-label">カテゴリー</label>
    <select name="category_id" id="category_id"
            class="form-select @error('category_id') is-invalid @enderror">
        <option value="">選択してください</option>
        @foreach ($categories as $category)
            <option value="{{ $category->id }}"
                @selected(old('category_id', $post->category_id ?? '') == $category->id)>
                {{ $category->name }}
            </option>
        @endforeach
    </select>
    @error('category_id')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="content" class="form-label">内容</label>
    <textarea name="content" id="content" rows="8"
              class="form-control @error('content') is-invalid @enderror">{{ old('content', $post->content ?? '') }}</textarea>
    @error('content')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="image" class="form-label">画像（任意）</label>

    {{-- 編集画面で、すでに画像がある場合は今の画像を表示する --}}
    @if ($post?->image_path)
        <div class="mb-2">
            {{-- Storage::disk('s3')->url(パス): DBに保存したパスから、S3の画像URLを作る --}}
            <img src="{{ Storage::disk('s3')->url($post->image_path) }}" alt="現在の画像"
                 class="img-thumbnail" style="max-height: 150px;">
            <div class="form-text">新しい画像を選ぶと差し替わります。</div>
        </div>
    @endif

    {{-- accept="image/*": ファイル選択画面で画像だけを表示する（チェック自体はPostRequestで行う） --}}
    <input type="file" name="image" id="image" accept="image/*"
           class="form-control @error('image') is-invalid @enderror">
    @error('image')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
<a href="{{ route('posts.index') }}" class="btn btn-secondary">キャンセル</a>
