<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Baseball SLG</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .title-container {
            background: white;
            border-radius: 20px;
            padding: 3rem;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 500px;
            width: 90%;
        }
        .title-icon {
            font-size: 5rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="title-container text-center">
        <div class="title-icon">⚾</div>
        <h1 class="display-4 fw-bold mb-3">Baseball SLG</h1>
        <p class="lead text-muted mb-5">プロ野球シミュレーションゲーム</p>

        <div class="d-grid gap-3">
            <a href="{{ route('login') }}" class="btn btn-primary btn-lg">
                ログイン
            </a>
            <a href="{{ route('register') }}" class="btn btn-outline-primary btn-lg">
                新規登録
            </a>
        </div>

        <div class="mt-5 pt-4 border-top">
            <p class="text-muted small mb-2">
                ゲストとして試合シミュレーターを利用する場合は
            </p>
            <a href="{{ route('game.index') }}" class="btn btn-outline-secondary btn-sm">
                ゲストモードで開始
            </a>
        </div>
    </div>
</body>
</html>

